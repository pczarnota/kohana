<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Szukaj extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'szablon'; //definiowanie zmiennej do obsługi widoków

    public function action_koszty() {
        $this->template->content = 'skoszty'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'xxxxxxxxx' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $this->session = Session::instance();
            $cos = $this->session->get('konto');


            if ($_POST) {
                $this->template->koszty = ORM::factory('cost')->where('konto', '=', $cos)->and_where_open()->where('sprzedaz', 'LIKE', '%' . $_POST['search'] . '%')->or_where('typ', 'LIKE', '%' . $_POST['search'] . '%')->or_where('data', 'LIKE', '%' . $_POST['search'] . '%')->or_where('ilosc', 'LIKE', '%' . $_POST['search'] . '%')->or_where('cena', 'LIKE', '%' . $_POST['search'] . '%')->and_where_close()->find_all();
                $this->template->sprzedaz = ORM::factory('sell')->where('konto', '=', $cos)->and_where_open()->where('data', 'LIKE', '%' . $_POST['search'] . '%')->or_where('nrfak', 'LIKE', '%' . $_POST['search'] . '%')->or_where('nr_faktury', 'LIKE', '%' . $_POST['search'] . '%')->or_where('nabywca', 'LIKE', '%' . $_POST['search'] . '%')->or_where('art_id', 'LIKE', '%' . $_POST['search'] . '%')->or_where('produkt', 'LIKE', '%' . $_POST['search'] . '%')->or_where('numer_transakcji', 'LIKE', '%' . $_POST['search'] . '%')->or_where('zrodlo_transakcji', 'LIKE', '%' . $_POST['search'] . '%')->and_where_close()->find_all();
            }
        }
    }

}

