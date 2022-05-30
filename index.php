<?php
// declare(strict_types=1);

require __DIR__ . './config/config.php';
require __DIR__ . './schema.php';
require __DIR__ . './utils.php';
require __DIR__ . './routes/register.php';
require __DIR__ . './routes/login.php';
require __DIR__ . './routes/user.php';

$data = json_decode(file_get_contents('php://input'));

// $arr = extract_uri($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']);
// echo json_encode($arr);
// return;

// echo json_encode($_SERVER);
// return

// maybe add some array of what to expect - just a suggestion

function a(string $p = '', int $f = 0)
{
    $p += $f;
    echo $p;
}

a();
echo 'echoed $a';
return;

$params = extract_requests(['user', 'project']);
echo json_encode($params);
return;

// we presume request has 3 layers
// example api/user?=jDoeNut/project?=122/delete
// ["user?=jDoeNut","project?=122","delete"]

// request handler... make this a function
switch ($slug) {
    case 'register':
        if ($user = register($data, connect())) {
            echo json_encode(set_response(true, null, $user));
        }
        break;
    case 'login':
        if ($user = login($data, connect())) {
            echo json_encode($user);
        }
        break;
    case 'user':
        // another lever of switch
        break;
    default:
        break;
}

// login, done
// next, fix register, done
// next, delete,
// next, update
// next, portfolio class
