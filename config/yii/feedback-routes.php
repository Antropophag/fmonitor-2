<?php
declare(strict_types=1);
return [
    "GET,HEAD pilot/feedback" => "feedback/form",
    "POST pilot/feedback" => "feedback/submit",
    "pilot/feedback" => "feedback/method",
    "GET,HEAD pilot/admin/feedback" => "feedback/admin",
    "pilot/admin/feedback" => "feedback/admin-method",
    "POST pilot/admin/feedback/<id:[1-9]\\d*>/result" => "feedback/result",
    "pilot/admin/feedback/<id:[1-9]\\d*>/result" => "feedback/result-method",
];
