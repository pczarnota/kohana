<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Kasuj extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'szablon'; //definiowanie zmiennej do obsługi widoków

    public function action_index() {
        $this->template->content = 'czyscbaze'; //załadowanie widoku
        $auth = Auth::instance(); //utworzenie instancji obiektu Auth
        $this->template->jest = 'xxxxxxxx' . $this->request->uri();
        if (!$auth->logged_in('login')) { //sprawdzanie czy użytkownik jest zalogowany
            $this->request->redirect('user/login'); //jeśli nie, przekierowujemy do logowania
        } else {

            $this->session = Session::instance();
            $cos = $this->session->get('konto');



            if ($_POST) {
                if ($_POST['id'])
                    foreach ($_POST['id'] as $id) {

                        if ($id == 1) {
                            $koszty = ORM::factory('cost')->where('konto', '=', $cos)->find_all();
                            foreach ($koszty as $koszt)
                                $koszt->delete();
                        }
                        if ($id == 2) {
                            $sprzedaz = ORM::factory('sell')->where('konto', '=', $cos)->find_all();
                            foreach ($sprzedaz as $sprzed) {
                                $faktureczka = ORM::factory('fakture')->where('nr_faktury', '=', $sprzed->nr_faktury)->and_where('konto', '=', $cos)->find();
                                if ($faktureczka->id)
                                    $faktureczka->delete();
                                if ($sprzed->reczna != 1) {
                                    $ebay = ORM::factory('ebay')->where('trans_id', '=', $sprzed->numer_transakcji)->find();
                                    if ($ebay->id)
                                        $ebay->delete();
                                }
                                $sprzed->delete();
                            }
                        }
                        if ($id == 3) {
                            $koszty = ORM::factory('faktur')->where('konto', '=', $cos)->find_all();
                            foreach ($koszty as $koszt)
                                $koszt->delete();
                        }
                    }
            }
        }
    }

}

