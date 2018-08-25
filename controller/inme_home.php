<?php
/**
 * This file is part of informame
 * Copyright (C) 2015-2018 Carlos Garcia Gomez <neorazorx@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Description of informame_home
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class inme_home extends fs_controller
{

    public $analytics;
    public $codfuente;
    public $buscar;
    public $keyword;
    public $modrewrite;
    public $mostrar;
    public $mostrar_tema;
    public $noticias;
    public $offset;
    public $page_description;
    public $page_title;
    public $preview;
    public $temas_populares;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Portada', 'informame');
    }

    public function anterior_url()
    {
        return $this->url() . '&mostrar=' . $this->mostrar . '&buscar=' . $this->buscar . '&codfuente=' .
            $this->codfuente . '&keyword=' . $this->keyword . '&offset=' . ($this->offset - FS_ITEM_LIMIT);
    }

    public function full_url()
    {
        $url = $this->empresa->web;

        if (isset($_SERVER['SERVER_NAME']) && $_SERVER['SERVER_NAME'] == 'localhost') {
            $url = '//' . $_SERVER['SERVER_NAME'];

            if (isset($_SERVER['REQUEST_URI'])) {
                $aux = parse_url(str_replace('/index.php', '', $_SERVER['REQUEST_URI']));
                $url .= $aux['path'];
            }
        }

        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }

        return $url;
    }

    public function get_keywords()
    {
        $txt = '';

        if ($this->temas_populares) {
            foreach ($this->temas_populares as $i => $tema) {
                if ($i == 0) {
                    $txt = $tema->codtema;
                } else if ($i < 9) {
                    $txt .= ', ' . $tema->codtema;
                }
            }
        }

        return $txt;
    }

    public function get_noticias($from, $to)
    {
        $noticias = [];
        foreach ($this->noticias as $key => $value) {
            if ($key >= $from && $key <= $to) {
                $noticias[] = $value;
            }
        }

        return $noticias;
    }

    public function siguiente_url()
    {
        return $this->url() . '&mostrar=' . $this->mostrar . '&buscar=' . $this->buscar . '&codfuente=' .
            $this->codfuente . '&keyword=' . $this->keyword . '&offset=' . ($this->offset + FS_ITEM_LIMIT);
    }

    public function split_temas($num = 4, $max = 16)
    {
        $actual = -1;
        $temas = [];
        foreach ($this->temas_populares as $key => $value) {
            if ($key >= $max) {
                break;
            }

            if ($key % $num === 0) {
                $actual++;
                $temas[$actual] = [];
            }

            $temas[$actual][] = $value;
        }

        return $temas;
    }

    public function total_noticias()
    {
        $data = $this->db->select("SELECT COUNT(id) as total FROM inme_noticias_fuente;");
        if ($data) {
            return intval($data[0]['total']);
        }

        return 0;
    }

    protected function private_core()
    {
        $this->procesar_portada();
    }

    protected function procesar_portada()
    {
        $this->mostrar = isset($_GET['mostrar']) ? $_GET['mostrar'] : 'portada';
        $this->buscar = isset($_REQUEST['buscar']) ? $_REQUEST['buscar'] : '';
        $this->codfuente = isset($_GET['codfuente']) ? $_GET['codfuente'] : '';
        $this->keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $this->offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        $this->preview = new inme_noticia_preview();

        $noti = new inme_noticia_fuente();
        if ($this->buscar != '') {
            $this->noticias = $noti->search($this->buscar, $this->offset);
        } else if ($this->codfuente != '') {
            $this->noticias = $noti->all_from_fuente($this->codfuente, $this->offset);
        } else if ($this->keyword != '') {
            $this->noticias = $noti->all_from_keyword($this->keyword, $this->offset);
        } else if ($this->mostrar == 'portada') {
            if ($this->offset > 0) {
                $this->noticias = $noti->all($this->offset, 'publicada DESC');
            } else {
                $this->noticias = $this->cache->get('inme_portada');
                if (!$this->noticias) {
                    $this->noticias = $noti->all($this->offset, 'publicada DESC');
                    $this->cache->set('inme_portada', $this->noticias, 300);
                }
            }
        } else if ($this->mostrar == 'populares') {
            $this->noticias = $noti->all($this->offset, 'popularidad DESC');
        } else {
            $this->noticias = $noti->all($this->offset);
        }

        $tema = new inme_tema();
        $this->mostrar_tema = isset($_GET['keyword']) ? $tema->get($_GET['keyword']) : FALSE;

        $this->temas_populares = $this->cache->get('inme_temas_populares');
        if (!$this->temas_populares) {
            $this->temas_populares = $tema->populares();
            $this->cache->set('inme_temas_populares', $this->temas_populares, 300);
        }
    }

    protected function public_core()
    {
        $this->template = 'inme_public/portada';
        $this->page_title = $this->empresa->nombrecorto;
        $this->page_description = 'Portal de noticias colaborativo, para los que huyen de la mafia de menéame.'
            . ' Exploramos la web para mostrarte los temas de actualidad.';

        $fsvar = new fs_var();
        $this->analytics = $fsvar->simple_get('inme_analytics');
        $this->modrewrite = $fsvar->simple_get('inme_modrewrite');

        if (isset($_GET['ok_cookies'])) {
            setcookie('ok_cookies', 'TRUE', time() + FS_COOKIES_EXPIRE, '/');
            $this->core_log->save('Se han aceptado las cookies', 'cookies');
        }

        $this->procesar_portada();
    }
}
