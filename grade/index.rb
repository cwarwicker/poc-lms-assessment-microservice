require "sinatra/base"
require "json"

class ExampleApp < Sinatra::Base
  set :bind, '0.0.0.0'
  set :port, 4567
  set :host_authorization, { permitted_hosts: [] }

  post '/process' do
      content_type :json

      # Just dump the received JSON back
      begin

        data = JSON.parse(request.body.read)

        # Store user's score.
        score = 0

        # Work out possible total score from all questions.
        total = 0

        # Example of processing grades
        data['questions'].each do |question|

            total += question['points']
            question_data = JSON.parse(question['data'])
            answer = data['answers']['question'][question['id'].to_s]

            # Choice
            if question['type'] == 'choice'

                answer_data = question_data['choices'][answer]

                # If choice is correct, award points.
                if answer_data['correct']
                   score += question['points']
                end

            elsif question['type'] == 'truefalse'

               # True/false questions use checkbox, so value will be "on" or missing entirely.
               correct = answer == "on"
               if correct
                   score += question['points']
               end

            elsif question['type'] == 'simpleinput'

                # For PoC assume case insensitive and exact match.
                question_data['answers'] = question_data['answers'].map(&:downcase)
                correct = question_data['answers'].include?(answer)

                if correct
                    score += question['points']
                end

            end

        end

        response = {}
        response[:quiz] = data['quiz_id']
        response[:score] = score.fdiv(total) # Moodle expects a score of between 0 and 1 to represent a percentage. Don't know why.
        response[:service_url] = data['service_url']
        response[:sourcedid] = data['sourcedid']

        # Return the received data as-is
        {
          response: response
        }.to_json

      rescue JSON::ParserError => e
        status 400
        { error: "Invalid JSON: #{e.message}" }.to_json
      end

  end
end

# This line runs the server
ExampleApp.run!

#curl -X POST poc-grade:4567/process -H "Content-Type: application/json" -d '{"quiz_id":1,"questions":[{"id":1,"quiz_id":1,"name":"Question 1","text":"What colour is the sky?","type":"choice","points":1,"data":"{\"choices\":{\"blue\":{\"text\":\"Blue\",\"correct\":true},\"green\":{\"text\":\"Green\",\"correct\":false},\"yellow\":{\"text\":\"Yellow\",\"correct\":false}},\"config\":[]}","created_at":"2025-07-21T15:52:08.000000Z","updated_at":"2025-07-21T15:52:08.000000Z"},{"id":2,"quiz_id":1,"name":"Question 2","text":"Chelsea FC are based in London?","type":"truefalse","points":1,"data":"{\"answer\":true,\"config\":[]}","created_at":"2025-07-21T15:52:08.000000Z","updated_at":"2025-07-21T15:52:08.000000Z"},{"id":3,"quiz_id":1,"name":"Question 3","text":"Name a programming language that can be used for back-end development","type":"simpleinput","points":3,"data":"{\"answers\":[\"PHP\",\"Ruby\",\"Python\",\"Java\",\"C++\",\"C#\",\"Golang\",\"Rust\",\"Javascript\"],\"config\":{\"casesensitive\":false,\"exactmatch\":true}}","created_at":"2025-07-21T15:52:08.000000Z","updated_at":"2025-07-21T15:52:08.000000Z"}],"answers":{"_token":"5pkgdit81m0Su9HtqVVXx6Nsdm9x3YqkQK6ztHM3","user_id":null,"quiz_id":"1","question":{"1":"blue","2":"on","3":"dsfdsf"}}}' -v
