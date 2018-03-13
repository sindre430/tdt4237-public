<?php
namespace App\Controllers;

use App\Models\ProductsModel;
use \App\System\App;
use \App\System\FormValidator;
use \App\System\Settings;
use \App\Controllers\Controller;
use \App\Models\CategoriesModel;
use \App\Models\RevisionsModel;
use \DateTime;

class CategoriesController extends Controller {

    public function all() {
        $model = new CategoriesModel();
        $data  = $model->all($_SESSION['user']);

        $this->render('pages/categories.twig', [
            'title'       => 'Categories',
            'description' => 'Categories - Just a simple inventory management system.',
            'page'        => 'categories',
            'categories'  => $data
        ]);
    }

    public function add() {
        if(!empty($_POST)) {
            $title       = isset($_POST['title']) ? $_POST['title'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';

            $validator = new FormValidator();
            $validator->notEmpty('title', $title, "Your title must not be empty");
            $validator->notEmpty('description', $description, "Your description must not be empty");

            if($validator->isValid()) {
                $model = new CategoriesModel();
                $model->create([
                    'title'       => filter_var($title, FILTER_SANITIZE_STRING),
                    'description' => filter_var($description, FILTER_SANITIZE_STRING),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'user'        => $_SESSION['user']
                ]);

                App::redirect('categories');
            }

            else {
                $this->render('pages/categories_add.twig', [
                    'title'       => 'Add category',
                    'description' => 'Categories - Just a simple inventory management system.',
                    'page'        => 'categories',
                    'errors'      => $validator->getErrors(),
                    'data'        => [
                        'title'       => filter_var($title, FILTER_SANITIZE_STRING),
                        'description' => filter_var($description, FILTER_SANITIZE_STRING)
                    ]
                ]);
            }
        }

        else {
            $this->render('pages/categories_add.twig', [
                'title'       => 'Add category',
                'description' => 'Categories - Just a simple inventory management system.',
                'page'        => 'categories'
            ]);
        }
    }

    public function edit($id) {
        if(!empty($_POST)) {
            $title       = isset($_POST['title']) ? $_POST['title'] : '';
            $description = isset($_POST['description']) ? $_POST['description'] : '';

            $validator = new FormValidator();
            $validator->notEmpty('title', $title, "Your title must not be empty");
            $validator->notEmpty('description', $description, "Your description must not be empty");

            if($validator->isValid()) {
                $model = new CategoriesModel();
                $model->update($id, [
                    'title'       => filter_var($title, FILTER_SANITIZE_STRING),
                    'description' => filter_var($description, FILTER_SANITIZE_STRING)
                ]);

                $revisions = new RevisionsModel();
                $revisions->create([
                    'type'    => 'categories',
                    'type_id' => $id,
                    'user'    => $_SESSION['auth']
                ]);

                App::redirect('categories');
            }

            else {
                $model = new RevisionsModel();
                $revisions = $model->revisions($id, 'categories');

                $this->render('pages/categories_edit.twig', [
                    'title'       => 'Edit category',
                    'description' => 'Categories - Just a simple inventory management system.',
                    'page'        => 'categories',
                    'revisions'   => $revisions,
                    'errors'      => $validator->getErrors(),
                    'data'        => [
                        'title'       => filter_var($title, FILTER_SANITIZE_STRING),
                        'description' => filter_var($description, FILTER_SANITIZE_STRING)
                    ]
                ]);
            }
        }

        else {
            $model = new CategoriesModel();
            $data = $model->find($id);

            if ($_SESSION['user'] === $data->user){
                $model2    = new RevisionsModel();
                $revisions = $model2->revisions($id, 'categories');

                $this->render('pages/categories_edit.twig', [
                    'title'       => 'Edit category',
                    'description' => 'Categories - Just a simple inventory management system.',
                    'page'        => 'categories',
                    'revisions'   => $revisions,
                    'data'        => $data
                ]);
            }else App::redirect('categories');
        }
    }

    public function delete($id) {
        $model2 = new ProductsModel($_SESSION['user']);
        $products = $model2->getProductsByCategoryId($id);
        
        if(!empty($_POST)) {
            $model = new CategoriesModel();
            $data = $model->find($id);
            if ($_SESSION['user'] === $data->user) {
                foreach ($products as $product) {
                    $model2->delete($product->id);
                }
                $model->delete($id);
            }
            App::redirect('categories');
        }

        else {
            $model = new CategoriesModel($_SESSION['user']);
            $data = $model->find($id);
            if ($_SESSION['user'] === $data->user){
                $this->render('pages/categories_delete.twig', [
                    'title'       => 'Delete category',
                    'description' => 'Categories - Just a simple inventory management system.',
                    'page'        => 'categories',
                    'data'        => $data,
                    'products'    => $products
                ]);
            }else App::redirect('categories');
        }
    }

    public function single($id, $slug) {
        $model = new CategoriesModel();
        $data  = $model->find($id);

        if($data->slug === $slug) {
            $this->render('pages/single.twig', [
                'title'       => 'Single',
                'description' => 'Just a simple inventory management system.',
                'page'        => 'products',
                'post' => $data
            ]);
        }

        else {
            App::error();
        }
    }
    
    public function api($id = null) {
        if($id) {
            $model = new CategoriesModel();
            $data  = $model->find($id);
            header('Content-Type: application/json');
            echo json_encode($data);
        }
        else {
            $model = new CategoriesModel();
            $data  = $model->all();
            header('Content-Type: application/json');
            echo json_encode($data);
        }
    }
}
