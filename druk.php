<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Druk extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'drukowanko'; //definiowanie zmiennej do obsługi widoków

    public function action_index() {
        $this->template->content = 'zestawienie2'; //załadowanie widoku

        $this->session = Session::instance();
        $cosik = $this->session->get('konto');

        if ($_GET) {

            $cosik = $_GET['konto'];

            $daty = explode(' - ', $_GET['da']);
            $cos = DB::select('id`,`nr_faktury`,`nabywca`, `auction_id`, `nazwisko`, `numer_transakcji`, `zrodlo_transakcji`, `ilosc`, `cena_jednostkowa`, `wartosc`, `waluta`, `data_sprzedazy`, `nrfak`, `wysylka`, GROUP_CONCAT(art_id SEPARATOR \'+\') AS `art_id')->from("sells")->where('data_sprzedazy', 'BETWEEN', array($daty[0], $daty[1]))->group_by('numer_transakcji')->order_by('data_sprzedazy', 'ASC');
            if ($_GET['firmy'] == 0)
                $cos->and_where('konto', '=', $cosik);
            $this->template->sprzedaz = $cos->as_object()->execute();
        }
    }

}
