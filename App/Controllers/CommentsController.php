<?php
namespace App\Controllers;

use App\Models\CommentsModel;
use \App\Controllers\Controller;
use \DateTime;
use App\System\App;

class CommentsController extends Controller {
    
    protected $table = "comments";

    public function add() {
        if(!empty($_POST)){
                $text  = isset($_POST['comment']) ? $_POST['comment'] : '';
                $model = new CommentsModel;
                $model->create([
                    'created_at' => date('Y-m-d H:i:s'),
                    'user'       => $_SESSION['user'],
                    'text'       => filter_var($text, FILTER_SANITIZE_STRING)
                ]);
            }
         App::redirect('dashboard');
       }
    }