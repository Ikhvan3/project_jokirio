<?php
// File: index.php (root project)

require_once './controller/MahasiswaController.php';

$act = isset($_GET['act']) ? $_GET['act'] : 'index';
$controller = new MahasiswaController();

switch ($act) {
    case 'create':
        $controller->create();
        break;
    case 'store':
        $controller->store();
        break;
    case 'edit':
        if (isset($_GET['id'])) {
            $controller->edit($_GET['id']);
        } else {
            header('Location: index.php');
        }
        break;
    case 'update':
        if (isset($_GET['id'])) {
            $controller->update($_GET['id']);
        } else {
            header('Location: index.php');
        }
        break;
    case 'delete':
        if (isset($_GET['id'])) {
            $controller->delete($_GET['id']);
        } else {
            header('Location: index.php');
        }
        break;
    default:
        $controller->index();
        break;
}
