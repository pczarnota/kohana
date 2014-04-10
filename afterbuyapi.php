<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Afterbuyapi extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'szablon'; //definiowanie zmiennej do obsługi widoków

    public function action_nowy() {
        $this->template->content = 'nafter'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth

        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {
            $this->SES = Session::instance();
            if ($this->SES->get('konto') != '') {
                if ($_POST) { //sprawdzanie czy dane są przesyłane POSTem
                    $walidacja = Validation::factory($_POST); //tworzenie obiektu walidacji
                    $walidacja->rule('token', 'not_empty')
                            ->rule('devid', 'not_empty')
                            ->rule('appid', 'not_empty')
                            ->rule('name', 'not_empty')
                            ->rule('certid', 'not_empty');

                    if ($walidacja->check()) {
                        $ebay = ORM::factory('ebayconfig'); //tworzenie obiektu ORM z użyciem tabeli users
                        $ebay->token = $_POST['token']; //przypisanie pola z formularza do nazwy kolumny w tabeli
                        $ebay->devid = $_POST['devid'];
                        $ebay->appid = $_POST['appid'];
                        $ebay->certid = $_POST['certid'];
                        $ebay->name = $_POST['name'];


                        if ($ebay->save()) {
                            $this->template->sukces = 'Dodano nowe konto Afterbuy.'; //przekazanie zmiennej $sukces do widoku
                        } else {
                            $this->template->fail = 'Nie udało się dodać koskontaztów!'; //przekazanie zmiennej $fail do widoku
                        }
                    } else {
                        $this->template->fail = 'Uzupełnij poprawnie formularz!';
                    }
                }
            }
        }
    }

}
