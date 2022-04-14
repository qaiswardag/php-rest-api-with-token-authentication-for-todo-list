<?php
// file for getting a single task


require_once 'db.php';
require_once '../model/Task.php';
require_once '../model/Response.php';

// 1: connect to the database
try {
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();

} catch (PDOException $e) {
    // 0 means that the error will be stored in the php error log file
    error_log("Connection error: " . $e, 0);
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage('Database connection error');
    $response->send();
    exit();
}


// in order to get a single task, we need to pass in a single task ID into the url

// check if task with id exist
// we are looking for the task id in the GET super global

// check if task id is exist e.g. /tasks/1
if (array_key_exists("taskid", $_GET)) {
    // get task id from query string
    $taskid = $_GET['taskid'];

    //check to see if task id in query string is not empty and is number, if not return json error
    if ($taskid == '' || !is_numeric($taskid)) {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage("Task ID cannot be blank or must be numeric");
        $response->send();
        exit;
    }

    // we need to check what the what the request method is
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {

        try {
            $query = $readDB->prepare('SELECT id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();
            // get row count
            $rowCount = $query->rowCount();

            // if not, if there is zero row count, then we can send a standard response to say not found
            if ($rowCount === 0) {
                $response = new Response();
                // http error code: 400: means not found
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("Task was not found");
                $response->send();
                exit();
            }

            // if task exist
            // for each row returned - should be just one
            // for each row returned
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                // create new task object for each row
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);

                // create task and store in array for return in json data
                $taskArray[] = $task->returnTaskAsArray();
            }

            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit();


        } catch (TaskException $e) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit();
        } catch (PDOException $e) {
            // 0 means that the error will be stored in the php error log file
            error_log("Database query error: " . $e, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get Task');
            $response->send();
            exit();
        }

    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        try {
            $query = $writeDB->prepare('delete from tbltasks where id = :taskid');
            $query->bindParam(':taskid', $taskid, PDO::PARAM_INT);
            $query->execute();

            // check if something have been deleted
            $rowCount = $query->rowCount();

            // if row does not exist with the given row id
            // catch will take care of errors
            if ($rowCount === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage('Task not found');
                $response->send();
                exit();
            }


            // if task is found
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->addMessage('Task deleted');
            $response->send();
            exit();

            // connection issue or query issue
        } catch (PDOException $e) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to delete task');
            $response->send();
            exit();
        }


    }

    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    } else {
        // create an error if request method is not GET, DELETE or PATCH
        // http error code: 405: means request method not allowed
        $response = new Response();
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage("Request method not allowed");
        $response->send();
        exit();
    }
}


// check if any completed or incompled tasks exist
// example:
// v1/tasks/completed
// v1/tasks/incompleted

// v1/controller/task.php?/completed=Y
// v1/controller/task.php?/completed=N
if (array_key_exists('completed', $_GET)) {

    // check for completed
    $completed = $_GET['completed'];


    if ($completed !== 'Y' && $completed !== 'N') {
        // send error response
        $response = new Response();

        // 400 http code since incorrect value have been passed
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Completed filter must be Y or N');
        $response->send();
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // get all tasks which have Y or N using the database connection
        try {
            // get tasks
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks where completed = :completed');
            $query->bindParam(':completed', $completed, PDO::PARAM_STR);
            $query->execute();

            // row count
            $rowCount = $query->rowCount();

            $taskArray = array();

            // while loop
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);
                $taskArray[] = $task->returnTaskAsArray();
            }

            // return data
            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData('good job');
            $response->setData($returnData);
            $response->send();
            exit();

            // catch
        } catch (TaskException $e) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit();
        } catch (PDOException) {
            error_log("Database query error – " . $e, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get tasks');
            $response->send();
            exit();
        }

    } else {
        $response = new Response();
        // http code 405: request method not allowed
        $response->setHttpStatusCode(405);
        $response->setSuccess(false);
        $response->addMessage('Request method not allowed');
        $response->send();
        exit();
    }

}

// get all tasks with pagination
// url: task.php?page=3
// pretty url: tasks/page/3
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // page
    $page = $_GET['page'];

    if ($page == '' || !is_numeric($page)) {
        $response = new Response();
        // http code 400: bad request
        $response->setHttpStatusCode(400);
        $response->setSuccess(false);
        $response->addMessage('Page number can not be blank and must be numeric');
        $response->send();
        exit();
    }

    // limit per page
    $limitPerPage = 20;

    // try
    try {
        // check how many row there is in the table
        $query = $readDB->prepare('select count(id) as totalNoOfTasks from tbltasks');
        $query->execute();
        $query->execute();

        $row = $query->fetch(PDO::FETCH_ASSOC);

        // convert to integer
        $tasksCount = intval($row['totalNoOfTasks']);

        // number of pages
        // divide amount of rows with how many row we want to show on each page
        $numOfPages = ceil($tasksCount / $limitPerPage);

        // if we have 0 tasks, we are not able to devide with 0
        // as minimum we want 1 page to be displayed
        if ($numOfPages == 0) {
            $numOfPages = 1;
        }

        // if there is only 2 pages, we have to send response saying page not found
        if ($page > $numOfPages) {
            $response = new Response();
            // 404 http code, since page do not exist
            $response->setHttpStatusCode(404);
            $response->setSuccess(false);
            $response->addMessage('Page not found');
            $response->send();
            exit();
        }

        // only get rows belonging to the page number
        $offset = ($page == 1 ? 0 : ($limitPerPage * ($page - 1)));

        // get relevant row based on page number
        $query = $readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks limit :pglimit offset :offset ');
        $query->bindParam(':pglimit', $limitPerPage, PDO::PARAM_INT);
        $query->bindParam(':offset', $offset, PDO::PARAM_INT);
        $query->execute();

        $rowCount = $query->rowCount();

        $taskArray = array();

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);

            $taskArray[] = $task->returnTaskAsArray();
        }

        $returnData = array();
        $returnData['rows_returned'] = $rowCount;
        $returnData['total_rows'] = $tasksCount;
        $returnData['total_pages'] = $numOfPages;
        ($page < $numOfPages ? $returnData['has_next_page'] = true : $returnData['has_next_page'] = false);
        ($page > 1 ? $returnData['has_previous_page'] = true : $returnData['has_previous_page'] = false);
        $returnData['tasks'] = $taskArray;


        $response = new Response();
        $response->setHttpStatusCode(200);
        $response->setSuccess(true);
        $response->toCache(true);
        $response->setData($returnData);
        $response->send();
        exit();


        // catch
    } catch (TaskException $e) {
        $response = new Response();
        // http code 500: server error
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage($e->getMessage());
        $response->send();
        exit();
        // catch
    } catch (PDOException $e) {
        error_log("Databse query error - " . $e, 0);
        $response = new Response();
        // http code 500: server error
        $response->setHttpStatusCode(500);
        $response->setSuccess(false);
        $response->addMessage('Failed to get tasks');
        $response->send();
        exit();
    }


} else {
    $response = new Response();
    // http code 405: request method not allowed
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage('Request method not allowed');
    $response->send();
    exit();
}


// handle CORS
// header('Access-Control-Allow-Methods: GET, POST, DELETE, PATCH');
// header('Access-Control-Allow-Headers: Authorization');
// header('Access-Control-Max-Age: 86400');

if (empty($_GET)) {

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // try
        try {
            $query = $readDB->prepare('select id, title, description, DATE_FORMAT(deadline, "%d/%m/%Y %H:%i") as deadline, completed from tbltasks');
            $query->execute();

            $taskArray = array();

            $rowCount = $query->rowCount();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                // for each row
                $task = new Task($row['id'], $row['title'], $row['description'], $row['deadline'], $row['completed']);

                $taskArray[] = $task->returnTaskAsArray();
            }


            // get all tasks
            $returnData = array();
            $returnData['rows_returned'] = $rowCount;
            $returnData['tasks'] = $taskArray;

            // new response
            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->setSuccess(true);
            $response->toCache(true);
            $response->setData($returnData);
            $response->send();
            exit();

            // catch
        } catch (TaskException $e) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage($e->getMessage());
            $response->send();
            exit();

            // catch
        } catch (PDOExeption $e) {
            error_log("Databse query error - " . $e, 0);
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage('Failed to get tasks');
            $response->send();
            exit();


        }

    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    }


    $response = new Response();
    // http code 405: request method not allowed
    $response->setHttpStatusCode(405);
    $response->setSuccess(false);
    $response->addMessage('Request method not allowed');
    $response->send();
    exit();
}
// if GET global variable is not empty
// e.g. v1/controller/task.php?test
if (!empty($_GET)) {
    $response = new Response();
    $response->setHttpStatusCode(404);
    $response->setSuccess(false);
    $response->addMessage('Endpoint not found');
    $response->send();
    exit();
}











