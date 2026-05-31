<?php
ob_start(); // Start output buffering to prevent header errors

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

error_reporting(E_ALL);
ini_set("display_errors", 1);

require __DIR__ . '/../vendor/autoload.php';

// Debug: Log what we're seeing
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'NOT SET'));
error_log("SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME']);

// Create Router instance
$router = new \Bramus\Router\Router();

$router->setNamespace('Controllers');

// Authentication
$router->post('/auth/register', 'UserController@register');
$router->post('/auth/login', 'UserController@login');
$router->post('/auth/refresh', 'UserController@refresh');
$router->get('/users/(\d+)', 'UserController@getUser');

// Projects
$router->get('/projects', 'ProjectController@getAllProjects');
$router->get('/projects/(\d+)', 'ProjectController@getProject');
$router->post('/projects', 'ProjectController@createProject');
$router->put('/projects/(\d+)', 'ProjectController@updateProject');
$router->delete('/projects/(\d+)', 'ProjectController@deleteProject');
$router->get('/projects/(\d+)/members', 'ProjectController@getProjectMembers');
$router->post('/projects/(\d+)/members', 'ProjectController@addProjectMember');
$router->put('/projects/(\d+)/members/(\d+)', 'ProjectController@updateProjectMember');
$router->delete('/projects/(\d+)/members/(\d+)', 'ProjectController@removeProjectMember');
$router->get('/projects/(\d+)/activity', 'ProjectController@getProjectActivity');

// Sprints
$router->get('/projects/(\d+)/sprints', 'SprintController@getSprints');
$router->get('/sprints/(\d+)', 'SprintController@getSprint');
$router->post('/sprints', 'SprintController@createSprint');
$router->put('/sprints/(\d+)', 'SprintController@updateSprint');
$router->post('/sprints/(\d+)/start', 'SprintController@startSprint');
$router->post('/sprints/(\d+)/complete', 'SprintController@completeSprint');
$router->post('/sprints/(\d+)/reopen', 'SprintController@reopenSprint');
$router->delete('/sprints/(\d+)', 'SprintController@deleteSprint');

// Tasks
$router->get('/projects/(\d+)/tasks', 'TaskController@getProjectTasks');
$router->get('/projects/(\d+)/backlog', 'TaskController@getProjectBacklogTasks');
$router->get('/sprints/(\d+)/tasks', 'TaskController@getSprintTasks');
$router->get('/tasks/(\d+)', 'TaskController@getTask');
$router->post('/tasks', 'TaskController@createTask');
$router->put('/tasks/(\d+)', 'TaskController@updateTask');
$router->patch('/tasks/(\d+)/status', 'TaskController@updateTaskStatus');
$router->post('/tasks/(\d+)/assign', 'TaskController@assignTask');
$router->delete('/tasks/(\d+)', 'TaskController@deleteTask');

// Teams
$router->get('/teams', 'TeamController@getMyTeams');
$router->get('/teams/(\d+)', 'TeamController@getTeam');
$router->post('/teams', 'TeamController@createTeam');
$router->put('/teams/(\d+)', 'TeamController@updateTeam');
$router->delete('/teams/(\d+)', 'TeamController@deleteTeam');
$router->post('/teams/(\d+)/invite', 'TeamController@inviteToTeam');
$router->delete('/teams/(\d+)/members/(\d+)', 'TeamController@removeMember');

// Invitations
$router->get('/users/me/invitations', 'TeamController@getMyInvitations');
$router->post('/invitations/(\d+)/accept', 'TeamController@acceptInvitation');
$router->post('/invitations/(\d+)/decline', 'TeamController@declineInvitation');

// Handle CORS preflight OPTIONS requests
$router->all('(.*)', function() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
});

// Run it!
$router->run();