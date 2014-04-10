<?php

defined('SYSPATH') or die('No direct script access.');

class Controller_Api extends Controller_Template { //dziedziczenie z Controller_Template

    public $template = 'pustka'; //definiowanie zmiennej do obsługi widoków

    public function action_index() {
        $this->template->content = 'api';
        $firmy = ORM::factory('firm')->order_by('nazwa', 'ASC')->find_all();
        foreach ($firmy as $firm) {
            $konto = $firm->id;
            $sprzedawca = ORM::factory('firm')->where('id', '=', '9')->find();
            if (file_exists('api/' . $firm->id))
                if (count(scandir('api/' . $firm->id)) >= 3) {
                    $dir = 'api/' . $firm->id;
                    $files = scandir('api/' . $firm->id);
                    unset($files[0]);
                    unset($files[1]);
                    foreach ($files as $f) {
                        $type = explode('.', $f);
                        if ($type[1] == 'xml') {
                            $xml = simplexml_load_file($dir . '/' . $f);
                            print_r($xml);
                            if (isset($xml->sells)) {
                                foreach ($xml->sells->sell as $sell) {
                                    $check = ORM::factory('sell')->where('konto', '=', $konto)->and_where('data', '=', $sell->data)->find();
                                    if ($check->id != '') {
                                        $check2 = ORM::factory('sell')->where('konto', '=', $konto)
                                                        ->and_where('numer_transakcji', '=', $sell->transid)
                                                        ->and_where('data', '=', $sell->data)
                                                        ->and_where('ilosc', '=', $sell->quantity)
                                                        ->and_where('art_id', '=', $sell->artid)
                                                        ->and_where('nazwisko', '=', $sell->name)
                                                        ->and_where('konto', '=', $konto)
                                                        ->and_where('cena_jednostkowa', '=', $sell->price)
                                                        ->and_where('wysylka', '=', $sell->send)
                                                        ->and_where('auction_id', '=', $sell->auctionid)
                                                        ->and_where('nabywca', '=', $sell->buyer)->find();
                                        if ($check2->id == '') {
                                            $selli = ORM::factory('sell');
                                            $selli->data = $sell->data;
                                            $selli->nr_faktury = $check->nr_faktury;
                                            $selli->nabywca = $sell->buyer;
                                            $selli->art_id = $sell->artid;
                                            $selli->data_sprzedazy = $sell->data;
                                            $selli->produkt = $sell->product;
                                            $selli->waluta = 'EUR';
                                            $selli->numer_transakcji = $sell->transid;
                                            $selli->nazwisko = $sell->name;
                                            $selli->auction_id = $sell->auctionid;
                                            $selli->wysylka = $sell->send;
                                            $selli->ilosc = $sell->quantity;
                                            $selli->zrodlo_transakcji = 5;
                                            $selli->cena_jednostkowa = $sell->price;
                                            $selli->wartosc = round($sell->price + ($sell->price * 0.19), 2);
                                            $selli->konto = $konto;
                                            $selli->save();
                                        }
                                    } else {


                                        if ($datka != $sell->data) {
                                            $przejscie++;
                                            $qwe = explode('-', $datka);
                                            $qwer = explode('-', $sell->data);
                                            if ($qwe[1] == $qwer[1]) {
                                                $tempnumer = explode('/', $nrfff);
                                                $nr = $tempnumer[0] + 1;
                                                $numerfaktury = $nr . '-' . $tempnumer[1] . '-' . $tempnumer[2];
                                                $nrfff = $nr . '/' . $tempnumer[1] . '/' . $tempnumer[2];
                                            } else {
                                                $rok = $qwer[0];
                                                $miesiac = $qwer[1];
                                                $faktura111 = ORM::factory('sell')->where('konto', '=', $konto)->and_where('data', 'LIKE', '%-' . $miesiac . '-%')->order_by('id', 'desc')->find();
                                                $numer = explode('/', $faktura111->nr_faktury);
                                                if (isset($numer[1])) {
                                                    if ($numer[1] == $miesiac) {
                                                        $nr = (int) $numer[0];
                                                        //do{
                                                        $nr++;
                                                        $numerfaktury = $nr . '-' . $miesiac . '-' . $rok;
                                                        $nrfff = $nr . '/' . $miesiac . '/' . $rok;
                                                        //}while($cos->id);
                                                    } else {
                                                        $numerfaktury = '1-' . $miesiac . '-' . $rok;
                                                        $nrfff = '1/' . $miesiac . '/' . $rok;
                                                    }
                                                } else {
                                                    $numerfaktury = '1-' . $miesiac . '-' . $rok;
                                                    $nrfff = '1/' . $miesiac . '/' . $rok;
                                                }
                                            }
                                        }
                                        $datka = $sell->data;

                                        $selli = ORM::factory('sell');
                                        $selli->data = $sell->data;
                                        $selli->nr_faktury = $nrfff;
                                        $selli->nabywca = $sell->buyer;
                                        $selli->art_id = $sell->artid;
                                        $selli->data_sprzedazy = $sell->data;
                                        $selli->produkt = $sell->product;
                                        $selli->waluta = 'EUR';
                                        $selli->numer_transakcji = $sell->transid;
                                        $selli->nazwisko = $sell->name;
                                        $selli->auction_id = $sell->auctionid;
                                        $selli->wysylka = $sell->send;
                                        $selli->ilosc = $sell->quantity;
                                        $selli->zrodlo_transakcji = 5;
                                        $selli->cena_jednostkowa = $sell->price;
                                        $selli->wartosc = round($sell->price + ($sell->price * 0.19), 2);
                                        $selli->konto = $konto;
                                        $selli->save();

                                        if (isset($sell->euro)) {
                                            $datka = explode('-', $sell->data);

                                            if (date('w', mktime(0, 0, 0, $datka[1], $datka[2], $datka[0])) == 1) {
                                                $datae = date('Y-m-d', mktime(0, 0, 0, $datka[1], $datka[2] - 3, $datka[0]));
                                            } else {
                                                $datae = date('Y-m-d', mktime(0, 0, 0, $datka[1], $datka[2] - 1, $datka[0]));
                                            }

                                            $checkeuro = ORM::factory('kur')->where('data', '=', $datae)->find();
                                            if ($checkeuro->id == '') {
                                                $euor = ORM::factory('kur');
                                                $euor->kurs = $sell->euro;
                                                $euor->data = $sell->data;
                                                $euor->save();
                                            }
                                        }

                                        $check3 = ORM::factory('fakture')->where(nr_faktury, '=', $nrfff)->and_where('konto', '=', $konto);
                                        if ($check3->id == '') {
                                            $fak1 = ORM::factory('fakture');
                                            $fak1->nr_faktury = $nrfff;
                                            $fak1->vat = 0;
                                            $fak1->s_nazwa = $sprzedawca->nazwa;
                                            $fak1->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                                            $fak1->s_nip = $sprzedawca->nip;
                                            $fak1->n_nazwa = $firm->nazwa;
                                            $fak1->n_adres = $firm->adres . '<br />' . $firm->kod_pocztowy . ' ' . $firm->miejscowosc;
                                            $fak1->n_nip = $firm->nip;
                                            $fak1->jezyk = 1;
                                            $fak1->n_kraj = $firm->kraj;
                                            $fak1->s_kraj = $sprzedawca->kraj;
                                            $fak1->konto = $konto;
                                            $fak1->n_id = $firm->id;
                                            $fak1->s_id = $sprzedawca->id;
                                            $fak1->save();
                                        }
                                    }
                                }
                            }
                            if (isset($xml->costs)) {
                                foreach ($xml->costs->cost as $cost) {
                                    $check5 = ORM::factory('cost')->where('konto', '=', $konto)
                                                    ->and_where('sprzedaz', '=', $cost->product)
                                                    ->and_where('data', '=', $cost->data)
                                                    ->and_where('ilosc', '=', $cost->quantity)
                                                    ->and_where('typ', '=', $cost->type)
                                                    ->and_where('cena', '=', $cost->price)
                                                    ->and_where('konto', '=', $konto)
                                                    ->and_where('vat', '=', $cost->vat)->find();
                                    if ($check5->id == '') {

                                        $koszt = ORM::factory('cost'); //tworzenie obiektu ORM z użyciem tabeli users
                                        $koszt->sprzedaz = $cost->product; //przypisanie pola z formularza do nazwy kolumny w tabeli
                                        $koszt->typ = $cost->type;
                                        $koszt->cena = $cost->price;
                                        $koszt->ilosc = $cost->quantity;
                                        $koszt->data = $cost->data;
                                        $koszt->data_sprzedazy = $cost->data;
                                        $koszt->waluta = 'EUR';
                                        $koszt->konto = $konto;
                                        $koszt->vat = $cost->vat;
                                        $koszt->n_nazwa = $sprzedawca->nazwa;
                                        $koszt->n_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                                        $koszt->n_nip = $sprzedawca->nip;
                                        $koszt->s_nazwa = $firm->nazwa;
                                        $koszt->s_adres = $firm->adres . '<br />' . $firm->kod_pocztowy . ' ' . $firm->miejscowosc;
                                        $koszt->s_nip = $firm->nip;
                                        $koszt->n_kraj = $sprzedawca->kraj;
                                        $koszt->n_id = $sprzedawca->id;
                                        $koszt->s_id = $firm->id;
                                        $koszt->s_kraj = $firm->kraj;
                                        $koszt->jezyk = 1;

                                        $rozklad = explode('-', $cost->data);
                                        $rok = $rozklad[0];
                                        $miesiac = $rozklad[1];
                                        $faktura = ORM::factory('cost')->where('konto', '=', $konto)->and_where('data', 'LIKE', '%-' . $miesiac . '-%')->order_by('id', 'desc')->find();
                                        $numer = explode('/', $faktura->nr);
                                        if (isset($numer[1])) {
                                            if ($numer[1] == $miesiac) {
                                                $nr = (int) $numer[0] + 1;
                                                $nrfff = $nr . '/' . $miesiac . '/' . $rok;
                                            } else {
                                                $nrfff = '1/' . $miesiac . '/' . $rok;
                                            }
                                        } else {
                                            $nrfff = '1/' . $miesiac . '/' . $rok;
                                        }
                                        $koszt->nr = $nrfff;
                                        $koszt->save();

                                        if (isset($cost->euro)) {
                                            $datka = explode('-', $cost->data);

                                            if (date('w', mktime(0, 0, 0, $datka[1], $datka[2], $datka[0])) == 1) {
                                                $datae = date('Y-m-d', mktime(0, 0, 0, $datka[1], $datka[2] - 3, $datka[0]));
                                            } else {
                                                $datae = date('Y-m-d', mktime(0, 0, 0, $datka[1], $datka[2] - 1, $datka[0]));
                                            }

                                            $checkeuro = ORM::factory('kur')->where('data', '=', $datae)->find();
                                            if ($checkeuro->id == '') {
                                                $euor = ORM::factory('kur');
                                                $euor->kurs = $cost->euro;
                                                $euor->data = $cost->data;
                                                $euor->save();
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($xml->corrections)) {
                                foreach ($xml->corrections->correction as $corr) {

                                    $check6 = ORM::factory('faktur')->where('konto', '=', $konto)
                                                    ->and_where('data_korygowanej', '=', $corr->datacor)
                                                    ->and_where('data', '=', $corr->data)
                                                    ->and_where('fak_korygowana', '=', $corr->nrfakt)
                                                    ->and_where('produkt1', '=', $corr->product)
                                                    ->and_where('sku', '=', $corr->sku)
                                                    ->and_where('netto', '=', $corr->price)->find();
                                    if ($check6->id == '') {


                                        $spr = ORM::factory('faktur')->where('data', '=', $corr->data)->and_where('fak_korygowana', '=', $corr->nrfakt)->and_where('konto', '=', $konto)->find();
                                        if ($spr->id != '') {
                                            $numeros = $spr->nr_faktury;
                                        } else {
                                            $spr2 = DB::query(Database::SELECT, 'SELECT * FROM `fakturs` WHERE `nr_faktury` LIKE \'%' . $corr->nr1 . '%\' AND `konto` = ' . $konto . ' ORDER BY substring_index(`nr_faktury`, \'/\', 1) * 1 DESC LIMIT 1')->as_object()->execute('default');

                                            //$spr2 = ORM::factory('faktur')->where('nr_faktury', 'LIKE', '%'.substr($data[$i][1], 1).'%')->order_by('nr_faktury', 'DESC')->find();
                                            if ($spr2[0]->id != '') {
                                                $num = explode('/', $spr2[0]->nr_faktury);
                                                $mu = $num[0] + 1;
                                                $numeros = $mu . '/' . $corr->nr1;
                                            }
                                            else
                                                $numeros = '1/' . $corr->nr1;
                                        }

                                        if (isset($corr->euro)) {
                                            $datka = explode('-', $corr->datacor);

                                            if (date('w', mktime(0, 0, 0, $datka[1], $datka[2], $datka[0])) == 1) {
                                                $datae = date('Y-m-d', mktime(0, 0, 0, $datka[1], $datka[2] - 3, $datka[0]));
                                            } else {
                                                $datae = date('Y-m-d', mktime(0, 0, 0, $datka[1], $datka[2] - 1, $datka[0]));
                                            }

                                            $checkeuro = ORM::factory('kur')->where('data', '=', $datae)->find();
                                            if ($checkeuro->id == '') {
                                                $euor = ORM::factory('kur');
                                                $euor->kurs = $corr->euro;
                                                $euor->data = $corr->datacor;
                                                $euor->save();
                                            }
                                        }

                                        $corecty = ORM::factory('faktur'); //tworzenie obiektu ORM z użyciem tabeli users
                                        $corecty->data = $corr->data;
                                        $corecty->data_korygowanej = $corr->datacor; //przypisanie pola z formularza do nazwy kolumny w tabeli
                                        $corecty->nr_faktury = $numeros;
                                        $corecty->powod = 'Zwrot';
                                        $corecty->fak_korygowana = $corr->nrfakt;
                                        $corecty->produkt1 = $corr->product;
                                        $corecty->ilosc1 = 0;
                                        $corecty->netto1 = 0;
                                        $corecty->produkt = $corr->product;
                                        $corecty->lp = $corr->lp;
                                        $corecty->sku = $corr->sku;
                                        $corecty->zrodlo_transakcji = 5;
                                        $corecty->ilosc = $corr->quantity;
                                        $corecty->netto = $corr->price;
                                        $corecty->waluta = 'EUR';
                                        $corecty->konto = $konto;
                                        $corecty->vat = 0;
                                        $corecty->s_nazwa = $sprzedawca->nazwa;
                                        $corecty->s_kraj = $sprzedawca->kraj;
                                        $corecty->s_adres = $sprzedawca->adres . '<br />' . $sprzedawca->kod_pocztowy . ' ' . $sprzedawca->miejscowosc;
                                        $corecty->s_nip = $sprzedawca->nip;
                                        $corecty->n_nazwa = $firm->nazwa;
                                        $corecty->n_adres = $firm->adres . '<br />' . $firm->kod_pocztowy . ' ' . $firm->miejscowosc;
                                        $corecty->n_nip = $firm->nip;
                                        $corecty->n_kraj = $firm->kraj;
                                        $corecty->n_id = $firm->id;
                                        $corecty->s_id = $sprzedawca->id;
                                        $corecty->save();
                                    }
                                }
                            }
                        }
                        unlink($dir . '/' . $f);
                    }
                }
        }
    }

}

