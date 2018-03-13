<?php
namespace App\Controllers;

use \App\System\App;
use \App\System\Settings;
use \App\System\FormValidator;
use \App\Controllers\Controller;
use \App\Models\UsersModel;
use \App\System\Auth;

class SessionsController extends Controller {

    public function login() {
        if(!empty($_POST)) {
            
            $username = isset($_POST['username']) ? $_POST['username'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            $error = $this->allowedLogin($username);

            if($error !== ""){
                $errors = [$error];
            }else if($this->auth->checkCredentials($username, $password)) {
                $_SESSION['auth']       = $username;
                $_SESSION['id']         = $this->userRep->getId($username);
                $_SESSION['email']      = $this->userRep->getEmail($username);
                $_SESSION['admin']      = $this->userRep->getAdmin($username);
                $_SESSION['user']       = $username;

                $this->removeAttemptsRow($_SESSION['id']);

                App::redirect('dashboard');
            } else {
                $user = $this->userRep->getUserRow($username);
                if ($user !== false) $this->updateAttempts($user);

                $errors = [
                    "Your username and your password don't match.",
                    $error
                ];
            }
        }

        $this->render('pages/signin.twig', [
            'title'       => 'Sign in',
            'description' => 'Sign in to the dashboard',
            'errors'      => isset($errors) ? $errors : ''
        ]);
    }

    public function logout() {
        App::redirect();
    }

    public function getUserRow($username){
        return App::getDb()->prepare('SELECT * FROM users WHERE username = ?', [$username], true);
    }

    public function getLoginAttemptsRow($id){
        return App::getDb()->prepare('SELECT * FROM login_attempts WHERE id = ?', [$id], true);
    }

    public function removeAttemptsRow($id){
        App::getDb()->execute('DELETE FROM login_attempts WHERE id = ?', [$id]);
    }

    public function updateAttempts($user){
        $id = $user->id;
        $loginAttempts = $this->getLoginAttemptsRow($id);
        if($loginAttempts->id){
            App::getDb()->execute('UPDATE login_attempts SET attempts = attempts+1, last_try = ? WHERE id = ?', [time(), $id]);
        }else{
            App::getDb()->execute('INSERT INTO login_attempts VALUES (?, 1, ?)', [$id, time()]);
        }
    }

    public function allowedLogin($username){
        $userRow = $this->getUserRow($username);
        if($userRow === false) return "";
        $loginAttempts = $this->getLoginAttemptsRow($userRow->id);
        if($loginAttempts === false) return "";
        $attempts = $loginAttempts->attempts;
        if($attempts <= 3) return "";
        else {
            $timestamp = $loginAttempts->last_try;
            if($attempts <= 5){
                if(time() - $timestamp > 10) return "";
                else return "Please wait 10 seconds.";
            }else if($attempts <= 10){
                if(time() - $timestamp > 20) return "";
                else return "Please wait 20 seconds.";
            }else if($attempts <= 30){
                if((time() - $timestamp) > 300) return "";
                else return "Please wait 5 minutes.";
            }else{
                if(time() - $timestamp > 3600) return "";
                else return "Please wait 1 hour.";
            }
        }
    }

}
