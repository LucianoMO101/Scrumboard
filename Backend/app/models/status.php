<?php

namespace Models;

// Status enum voor Task statussen (Kanban board)
enum TaskStatus: string {
    case Backlog = 'backlog';
    case Todo = 'todo';
    case Doing = 'doing';
    case Done = 'done';
}

// Status enum voor Sprint statussen
enum SprintStatus: string {
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
}

// Role enum voor Project roles (authorization)
enum ProjectRole: string {
    case Owner = 'owner';
    case Editor = 'editor';
    case Viewer = 'viewer';
}

?>