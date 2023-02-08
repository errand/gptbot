<?php

declare(strict_types=1);

namespace Kottenberg;

use OpenAI;

class Telegram
{
    /**
     * Creates a Client instance with the given API token and Webhook url.
     */
    public function __construct(string $gpt_token, string $webkook_url)
    {
        $this->gpt_token = $gpt_token;
        $this->api_url = $webkook_url;
    }

    public function apiRequestWebhook($method, $parameters): bool
    {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            error_log("Parameters must be an array\n");
            return false;
        }

        $parameters["method"] = $method;

        $payload = json_encode($parameters);
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($payload));
        echo $payload;

        return true;
    }

    public function exec_curl_request($handle)
    {
        $response = curl_exec($handle);

        if ($response === false) {
            $errno = curl_errno($handle);
            $error = curl_error($handle);
            error_log("Curl returned error $errno: $error\n");
            curl_close($handle);
            return false;
        }

        $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
        curl_close($handle);

        if ($http_code >= 500) {
            // do not wat to DDOS server if something goes wrong
            sleep(10);
            return false;
        } else if ($http_code != 200) {
            $response = json_decode($response, true);
            error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
            if ($http_code == 401) {
                throw new Exception('Invalid access token provided');
            }
            return false;
        } else {
            $response = json_decode($response, true);
            if (isset($response['description'])) {
                error_log("Request was successful: {$response['description']}\n");
            }
            $response = $response['result'];
        }

        return $response;
    }

    public function apiRequest($method, $parameters)
    {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            error_log("Parameters must be an array\n");
            return false;
        }

        foreach ($parameters as $key => &$val) {
            // encoding to JSON array parameters, for example reply_markup
            if (!is_numeric($val) && !is_string($val)) {
                $val = json_encode($val);
            }
        }
        $url = $this->api_url . $method . '?' . http_build_query($parameters);

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);

        return $this->exec_curl_request($handle);
    }

    public function apiRequestJson($method, $parameters)
    {
        if (!is_string($method)) {
            error_log("Method name must be a string\n");
            return false;
        }

        if (!$parameters) {
            $parameters = array();
        } else if (!is_array($parameters)) {
            error_log("Parameters must be an array\n");
            return false;
        }

        $parameters["method"] = $method;

        $handle = curl_init($this->api_url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, 60);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
        curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

        return $this->exec_curl_request($handle);
    }

    public function processMessage($message)
    {
        // process incoming message
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        if (isset($message['text'])) {
            // incoming text message
            $text = $message['text'];

            if (strpos($text, "/start") === 0) {
                $this->apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Chat is working in development mode using default settings. Type text using recommendation https://openai.com/blog/instruction-following/', 'reply_markup' => array(
                    'keyboard' => array(array('Model', 'Max tokens', 'Temperature')),
                    'one_time_keyboard' => true,
                    'resize_keyboard' => true)));
            } else if ($text === "Model" || $text === "Max tokens" || $text === "Temperature") {
                $this->apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Work in progress. Come back later'));
            } else if (strpos($text, "/stop") === 0) {
                // stop now
            } else {

                $client = OpenAI::client($this->gpt_token);
                $result = $client->completions()->create([
                    'model' => 'text-davinci-003',
                    'prompt' => $text,
                    'max_tokens' => 2000,
                    'temperature' => 0.8,
                    'top_p' => 1,
                    "frequency_penalty" => 0,
                    "presence_penalty" => 0
                ]);
                $this->apiRequestWebhook("sendMessage", array('chat_id' => $chat_id, "reply_to_message_id" => $message_id, "text" => $result['choices'][0]['text']));
            }
        } else {
            $this->apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages'));
        }
    }
}
