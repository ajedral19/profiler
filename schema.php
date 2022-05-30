<?php

declare(strict_types=1);

class User
{
    // using trait - alternative for abstracting project and job class
    // user class is now force to implement/override some specific method that are declared in traits
    // use Project, Job;

    public string $id, $firstname, $lastname, $email, $username;
    private string $password;
    private ?object $conn;
    public bool $success;
    // public $response;

    public function __construct(object $data, ?object $conn = null)
    {
        $this->conn = $conn ?? null;
        $this->id =
            !isset($data->id) || empty($data->id) ? generateId() : $data->id;
        $this->firstname = $data->firstname;
        $this->lastname = $data->lastname;
        $this->email = $data->email;
        $this->username = $data->username;
        $this->password = $data->password ?? null;
    }

    public function closeConn(): void
    {
        $this->db = (object) [];
    }

    public static function new_account(object $data, object $conn): ?User
    {
        // bool | User
        // bool | User
        // if user exist return null
        if (User::user_exist($data->username, $conn)) {
            print json_encode(
                set_response(false, "user $data->username already exists")
            );
            $conn = null;
            return null;
        }

        // if password and repassword is not present return null
        if (!is_null(validate_fields($data, ['password', 'repassword']))) {
            $conn = null;
            return null;
        }

        // if password and repassword do not match return null
        if (!User::match_password($data->password, $data->repassword)) {
            echo json_encode(set_response(false, "password don't match."));
            $conn = null;
            return null;
        }

        // probably utility functions for
        // validating emails validity
        // validating usernames validity
        // generating hash password

        // save user
        $user = new User($data);
        $query =
            'INSERT INTO users (id, firstname, lastname, email, uname, pword) VALUES (\'%1$s\', \'%2$s\', \'%3$s\', \'%4$s\', \'%5$s\', \'%6$s\')';
        $query = sprintf(
            $query,
            $user->id,
            $user->firstname,
            $user->lastname,
            $user->email,
            $user->username,
            $data->password // must be hashed password to be stored
        );
        $stmt = $conn->prepare($query);
        User::execute($stmt, $conn);
        $conn = null;
        return $user;
    }

    public static function login(object $data, object $conn): ?User
    {
        // check if there's missing field
        if (!is_null(validate_fields($data, ['username', 'password']))) {
            $conn = null;
            return null;
        }
        // check if user exist
        if (!User::user_exist($data->username, $conn)) {
            print json_encode(
                set_response(false, "user $data->username does not exists")
            );
            $conn = null;
            return null;
        }
        // match password / match hashed password
        $user = User::get_user($data->username, $conn);
        if (!User::match_password($data->password, $user->password)) {
            echo json_encode(set_response(false, 'your password is incorrect'));
            return null;
        }

        $user = new User($user);
        return $user;
    }

    // delete account -> transfer to archive
    public function delete_account(): void
    {
        // check if user exist
        if (!User::user_exist($this->username, $this->conn)) {
            print json_encode(
                set_response(false, "user $this->username does not exists")
            );
            return;
        }

        // delete user - delete account should delete also profile data, but have the option download profile data in json format
        $query = 'DELETE FROM users WHERE uname = :i;';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':i', $this->username);
        User::execute($stmt, $this->conn);
        set_response(true, "User $this->username account has been deleted");
        return;
    }

    // only name, username, email and display image can be updated
    public function save_update(User $user): ?User
    {
        // check if user exists
        if (!User::user_exist($this->username, $this->conn)) {
            print json_encode(
                set_response(false, "user $user->username does not exists")
            );
            return null;
        }

        // must validate email and username if used by other users
        if (
            $user->username !== $this->username &&
            $user->email !== $this->email
        ) {
            $toFind = [$user->username, $user->email];
        } elseif ($user->username !== $this->username) {
            $toFind = $user->username;
        } elseif ($user->email !== $this->email) {
            $toFind = $user->email;
        }

        if (
            User::user_exist(
                is_array($toFind) ? $toFind[0] : $toFind,
                $this->conn,
                is_array($toFind) ? $toFind[1] : null
            )
        ) {
            print json_encode(
                set_response(
                    false,
                    'you are trying to use and already existing credential'
                )
            );
            return null;
        }

        // else / the truth
        $this->firstname = $user->firstname;
        $this->lastname = $user->lastname;
        $this->email = $user->email;
        $this->username = $user->username;

        $query =
            'UPDATE users SET firstname = \'%1$s\', lastname = \'%2$s\' email = \'%3$s\' uname = \'%4$s\';';
        $query = sprintf(
            $query,
            $this->firstname,
            $this->lastname,
            $this->email,
            $this->username
        );
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);

        return $this;
        // check if the data property has changed then
        // update $this properties with provided data
        // then return $this
        // print response displaying $this
    }

    private static function get_user(string $username, object $db): object
    {
        $query =
            'SELECT id, firstname, lastname, email, uname as username, pword as password FROM users WHERE uname = :i OR email = :i';
        $stmt = $db->prepare($query);
        $stmt->bindParam(':i', $username);
        User::execute($stmt, $db);
        $db = null;
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    private static function match_password(
        string $password,
        string $repassword
    ): bool {
        return $password === $repassword ? true : false;
    }

    // note. response success property is statically set to false as response represents error
    protected static function user_exist(
        string $username,
        object $db,
        ?string $email = null
    ): bool {
        $query =
            'SELECT EXISTS(SELECT 1 FROM users WHERE uname=\'%1$s\' OR email=\'%2$s\') AS result;';
        $query = sprintf($query, $username, $email);
        $stmt = $db->prepare($query);
        User::execute($stmt, $db);
        $user = $stmt->fetch(PDO::FETCH_OBJ);
        $db = null;
        return boolval($user->result);
    }

    protected static function execute($stmt, $conn): void
    {
        $db = $conn;
        $db->beginTransaction();
        $stmt->execute();
        $db->commit();
        $db = null;
    }

    // next
    // implement crud operation for job
    // implement crud operation for projects
    // implement dispatching of data to consumer for initial use
    // improve input validation particularly in username and email
    // finally create an interface for managing and dispatching profile data

    protected function getter()
    {
    }
}

// why it extends user? project is relative to user, a user must exist to do some actions in projects table
class Project extends User
{
    // instantiate new project only to an existing user
    public string $p_id, $title, $subject, $category, $description;

    public function __construct(object $data)
    {
        if (isset($data->id) && !empty($data->id)) {
            $this->p_id = $data->id;
        }
        $this->title = $data->title;
        $this->subject = $data->subject;
        $this->category = $data->category;
        $this->description = $data->description;
    }

    // saves new project
    public function new_project(object $data): ?Project
    {
        // verify if user exists? maybe do this in implementation of projects.php file
        $query =
            'INSERT INTO projects (title, _subject, _description, _url, category, _user_id ) VALUES (\'%1$s\', \'%2$s\', \'%3$s\', \'%4$s\', \'%5$s\')';
        $query = sprintf(
            $query,
            $this->title,
            $this->subject,
            $this->category,
            $this->description,
            $this->id
        );
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);

        return new Project(Project::get_project($data->id));
    }

    private static function get_project($id): ?object
    {
        return null;
    }

    // updates existing project
    public function update_project(Project $project): ?Project
    {
        $this->title = $project->title;
        $this->subject = $project->subject;
        $this->category = $project->category;
        $this->description = $project->description;

        $query =
            'UPDATE projects SET title = \'\', _subject = \'\', category = \'\' _description = \'\' WHERE id = \'\' AND _user_id = \'\';';
        $query = sprintf(
            $query,
            $this->title,
            $this->subject,
            $this->category,
            $this->description,
            $project->p_id,
            $this->id
        );
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);

        return $this;
    }

    // deletes existing project
    // maybe create and archive for projects
    public function delete_project(): void
    {
        $query = 'DELETE FROM projects WHERE id =\'\' AND _user_id = \'\';';
        $query = sprintf($query, $this->p_id, $this->id);
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);
    }
    // get a list of projects
}

class Job extends User
{
    public string $j_id,
        $title,
        $position,
        $yr_start,
        $company,
        $website,
        $description;
    public ?string $yr_end;

    public function __construct(object $data)
    {
        if (isset($data->id) || !empty($data->id)) {
            $this->j_id = $data->id;
        }
        $this->title = $data->title;
        $this->position = $data->position;
        $this->company = $data->company;
        $this->yr_start = $data->year_start;
        $this->yr_end =
            isset($data->year_end) && !empty($data->year_end)
                ? $data->year_end
                : null;
        $this->website = $data->website;
        $this->desciption = $data->desciption;
    }

    // create new job
    public function new_job(): ?Job
    {
        $query =
            'INSERT INTO jobs (title, position, company, yrstart, yrend, companywebsite, _description, _user_id) VALUES (\'%1$s\', \'%2$s\', \'%3$s\', \'%4$s\', \'%5$s\', \'%6$s\', \'%7$s\', \'%8$s\';);';
        $query = sprintf(
            $query,
            $this->title,
            $this->position,
            $this->company,
            $this->year_start,
            $this->year_end,
            $this->website,
            $this->description,
            $this->id
        );
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);

        return new Job(Job::get_job($this->id, $this->conn));
    }

    public function update_job(Job $data): ?Job
    {
        $this->title = $data->title;
        $this->position = $data->position;
        $this->company = $data->company;
        $this->year_start = $data->year_start;
        $this->year_end = $data->year_end;
        $this->website = $data->website;
        $this->description = $data->description;

        $query =
            'UPDATE jobs SET title = \'$1%s\', position = \'$2%s\', company = \'$3%s\', yrstart = \'$4%s\', yrend = \'$5%s\', conpanywebsite = \'$6%s\', _description \'$7%s\' WHERE id = \'$8%s\' AND _user_id = \'$9%s\';';
        $query = sprintf(
            $query,
            $this->title,
            $this->position,
            $this->company,
            $this->year_start,
            $this->year_end,
            $this->website,
            $this->description,
            $this->j_id,
            $this->id
        );
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);
        return $this;
    }

    public function delete_job(): void
    {
        $query = 'DELETE FROM jobs WHERE id = \'\' AND _user_id = \'\'';
        $query = sprintf($query, $this->conn);
        $stmt = $this->conn->prepare($query);
        User::execute($stmt, $this->conn);
    }

    private static function get_job($id, $conn): ?object
    {
        $query = 'SELECT * FROM jobs WHERE id = :id';
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $id);
        User::execute($stmt, $conn);
        return null;
    }

    // update job
    // delete job
}
