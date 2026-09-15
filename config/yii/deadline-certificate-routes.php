<?php
declare(strict_types=1);
return [
    'GET,HEAD pilot/objects/<id:[1-9]\\d*>/deadline-certificates' => 'deadline-certificate/index',
    'POST pilot/objects/<id:[1-9]\\d*>/deadline-certificates' => 'deadline-certificate/submit',
    'GET,HEAD pilot/objects/<id:[1-9]\\d*>/deadline-certificates/<revision:[1-9]\\d*>/pdf' => 'deadline-certificate/pdf',
    'pilot/objects/<id:[1-9]\\d*>/deadline-certificates' => 'deadline-certificate/index-method',
    'pilot/objects/<id:[1-9]\\d*>/deadline-certificates/<revision:[1-9]\\d*>/pdf' => 'deadline-certificate/pdf-method',
];
