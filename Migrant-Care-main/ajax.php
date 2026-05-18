<?php
// /migrantcare/ajax.php

session_start();
require_once 'db.php';
require_once 'functions.php';

// Check what action the frontend is requesting
$action = $_GET['action'] ?? '';

switch ($action) {

    // Handle the request from 'ai_symptom_checker.php'
    case 'ai_chat':
        // Ensure the user is logged in to use the chat
        requireLogin();
        
        // Get the JSON payload sent from the JavaScript
        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';

        if (empty($message)) {
            $reply = "I'm sorry, I didn't receive a message.";
        } else {
            // Call the new conversational function (which we will add to functions.php)
            $reply = getAIChatResponse($message);
        }

        // Send the AI's reply back to the JavaScript as JSON
        header('Content-Type: application/json');
        echo json_encode(['reply' => $reply]);
        exit();

    // You can add more AJAX actions here in the future
    // case 'other_action':
    //     // ...
    //     break;

    default:
        // Send an error if the action isn't recognized
        header('Content-Type: application/json');
        echo json_encode(['reply' => 'Error: Invalid action.']);
        exit();
}
?>