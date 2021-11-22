<?php

require("azure-webhook.local.php");

$username = $_SERVER["PHP_AUTH_USER"];
$password = $_SERVER["PHP_AUTH_PW"];

if (!password_verify($username, $basicuser) || !password_verify($password, $basicpass)) {
        http_response_code(401);
        die();
}

// Based on https://github.com/KostblLb/azure-discord-webhook
$in_data = json_decode(file_get_contents('php://input'));

function azure_commit_to_discord_embed($commit) {
        $title = $commit->comment;
        $description = "";
        if (strlen($title) > 255) {
                $description = $title;
                $title = substr($title, 0, 252) . "...";
        }

        global $in_data;

        return [
                "title" => $title,
                "type" => "rich",
                "description" => $description,
                "url" => $in_data->resource->repository->remoteUrl . "/commit/" . $commit->commitId,
                "timestamp" => $commit->committer->date,
                "author" => [
                        "name" => $commit->committer->name
                ]
        ];
}


// Based on https://gist.github.com/Mo45/cb0813cb8a6ebcd6524f6a36d4f8862c
$out_data = json_encode([
        // Message
        //"content" => "[TEST MODE] Webhook event",
        "content" => $in_data->message->markdown,
        "embeds" => array_map('azure_commit_to_discord_embed', $in_data->resource->commits)
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );


$ch = curl_init( $webhookurl );
curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
curl_setopt( $ch, CURLOPT_POST, 1);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $out_data);
curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt( $ch, CURLOPT_HEADER, 0);
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

curl_exec( $ch );
curl_close( $ch );
