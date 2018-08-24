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
 * Description of inme_noticia_fuente
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class inme_noticia_fuente extends fs_model
{

    public $id;
    public $id_relacionada;
    public $url;
    public $titulo;
    public $texto;
    public $resumen;
    public $fecha;
    public $publicada;
    public $codfuente;
    public $likes;
    public $tweets;
    public $meneos;
    public $preview;
    public $editada;
    public $destacada;
    public $nativa;
    public $parodia;
    public $meneame_link;
    public $permalink;
    private $popularidad;
    private $keywords;

    public function __construct($n = FALSE)
    {
        parent::__construct('inme_noticias_fuente');
        if ($n) {
            $this->id = $this->intval($n['id']);
            $this->id_relacionada = $this->intval($n['id_relacionada']);
            $this->url = $n['url'];
            $this->titulo = $n['titulo'];
            $this->texto = $n['texto'];
            $this->resumen = $n['resumen'];
            $this->fecha = date('d-m-Y H:i:s', strtotime($n['fecha']));

            $this->publicada = NULL;
            if ($n['publicada']) {
                $this->publicada = date('d-m-Y H:i:s', strtotime($n['publicada']));
            }

            $this->codfuente = $n['codfuente'];
            $this->likes = intval($n['likes']);
            $this->tweets = intval($n['tweets']);
            $this->meneos = intval($n['meneos']);
            $this->popularidad = intval($n['popularidad']);
            $this->keywords = $n['keywords'];
            $this->preview = $n['preview'];
            $this->editada = $this->str2bool($n['editada']);
            $this->destacada = $this->str2bool($n['destacada']);
            $this->nativa = $this->str2bool($n['nativa']);
            $this->parodia = $this->str2bool($n['parodia']);
            $this->meneame_link = $n['meneame_link'];
            $this->permalink = $n['permalink'];
        } else {
            $this->id = NULL;
            $this->id_relacionada = NULL;
            $this->url = NULL;
            $this->titulo = NULL;
            $this->texto = NULL;
            $this->resumen = NULL;
            $this->fecha = date('d-m-Y H:i:s');
            $this->publicada = NULL;
            $this->codfuente = NULL;
            $this->likes = 0;
            $this->tweets = 0;
            $this->meneos = 0;
            $this->popularidad = 0;
            $this->keywords = '';
            $this->preview = NULL;
            $this->editada = FALSE;
            $this->destacada = FALSE;
            $this->nativa = TRUE;
            $this->parodia = FALSE;
            $this->meneame_link = NULL;
            $this->permalink = NULL;
        }
    }

    protected function install()
    {
        /// forzamos la comprobaciones de la tabla de fuentes
        new inme_fuente();

        return '';
    }

    public function url($modrewrite = FALSE)
    {
        if ($this->editada) {
            if ($modrewrite) {
                return 'story/' . $this->permalink;
            } else {
                return $this->edit_url();
            }
        } else
            return $this->url;
    }

    public function edit_url()
    {
        return 'index.php?page=inme_editar_noticia&amp;id=' . $this->id;
    }

    public function popularidad()
    {
        $tclics = $this->tweets + $this->likes + $this->meneos;
        $dias = 1 + intval((time() - strtotime($this->fecha)) / 86400);

        if (strlen($this->titulo) < 10 OR strlen($this->texto) < 100 AND $tclics > 0) {
            /// si el título o el texto es muy corto, lo penalizamos.
            $tclics = $tclics / 2;
        }

        if (!$this->nativa AND $tclics > 0) {
            /// si la noticia no está en español, también penalizamos
            $tclics = $tclics / 2;
        }

        /// damos una bonificación por cada keyword
        foreach ($this->keywords() as $key) {
            $tclics++;
        }

        $this->popularidad = 0;
        if ($tclics > 0) {
            /// la popularidad debe bajar con el paso del tiempo
            $this->popularidad = intval($tclics / $dias);

            /// aun así hay noticias con millones de clics, así que dividimos por semanas
            if ($dias > 7) {
                $semanas = pow(2, intval($dias / 7));
                $this->popularidad = intval($this->popularidad / $semanas);
            }
        }

        return $this->popularidad;
    }

    public function keywords($plain = FALSE)
    {
        $keys = [];

        $aux = explode(',', $this->keywords);
        if ($aux) {
            foreach ($aux as $i => $value) {
                $key = str_replace(array('[', ']'), array('', ''), $value);
                if ($key) {
                    $keys[] = $key;
                }
            }
        }

        if ($plain) {
            return join(', ', $keys);
        } else {
            return $keys;
        }
    }

    public function set_keyword($k)
    {
        if ($this->keywords == '') {
            $this->keywords = '[' . strtolower($k) . ']';
        } else if (!in_array($k, $this->keywords())) {
            $this->keywords .= ',[' . strtolower($k) . ']';
        }
    }

    public function clean_keywords()
    {
        $this->keywords = NULL;
    }

    private function new_permalink()
    {
        $url_title = substr(strtolower($this->titulo), 0, 60);
        $changes = array('/à/' => 'a', '/á/' => 'a', '/â/' => 'a', '/ã/' => 'a', '/ä/' => 'a',
            '/å/' => 'a', '/æ/' => 'ae', '/ç/' => 'c', '/è/' => 'e', '/é/' => 'e', '/ê/' => 'e',
            '/ë/' => 'e', '/ì/' => 'i', '/í/' => 'i', '/î/' => 'i', '/ï/' => 'i', '/ð/' => 'd',
            '/ñ/' => 'n', '/ò/' => 'o', '/ó/' => 'o', '/ô/' => 'o', '/õ/' => 'o', '/ö/' => 'o',
            '/ő/' => 'o', '/ø/' => 'o', '/ù/' => 'u', '/ú/' => 'u', '/û/' => 'u', '/ü/' => 'u',
            '/ű/' => 'u', '/ý/' => 'y', '/þ/' => 'th', '/ÿ/' => 'y', '/ñ/' => 'ny',
            '/&quot;/' => '-'
        );
        $url_title = preg_replace(array_keys($changes), $changes, $url_title);
        $url_title = preg_replace('/[^a-z0-9]/i', '-', $url_title);
        $url_title = preg_replace('/-+/', '-', $url_title);

        if (substr($url_title, 0, 1) == '-') {
            $url_title = substr($url_title, 1);
        }

        if (substr($url_title, -1) == '-') {
            $url_title = substr($url_title, 0, -1);
        }

        $url_title .= '-' . mt_rand(0, 999) . '.html';

        return $url_title;
    }

    public function get($id)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new inme_noticia_fuente($data[0]);
        } else {
            return FALSE;
        }
    }

    public function get_by_url($url)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE url = " . $this->var2str($url) . ";");
        if ($data) {
            return new inme_noticia_fuente($data[0]);
        } else {
            return FALSE;
        }
    }

    public function get_by_titulo($titulo)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE titulo = " . $this->var2str($titulo) . ";");
        if ($data) {
            return new inme_noticia_fuente($data[0]);
        } else {
            return FALSE;
        }
    }

    public function get_by_permalink($permalink)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE permalink = " . $this->var2str($permalink) . ";");
        if ($data) {
            return new inme_noticia_fuente($data[0]);
        } else {
            return FALSE;
        }
    }

    public function exists()
    {
        if (is_null($this->permalink)) {
            $this->permalink = $this->new_permalink();
        }

        if (is_null($this->id)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
        }
    }

    public function save()
    {
        $this->titulo = $this->no_html($this->titulo);
        $this->resumen = $this->no_html($this->resumen);

        if ($this->preview) {
            if (substr($this->preview, 0, 7) == 'http://') {
                $this->preview = NULL;
                $this->new_error_msg('Ya no se admiten imágenes http. Imagen eliminada.');
            }
        } else {
            $this->preview = NULL;
        }

        /// calculamos la popularidad
        $this->popularidad();

        if ($this->exists()) {
            $sql = "UPDATE " . $this->table_name . " SET url = " . $this->var2str($this->url)
                . ", titulo = " . $this->var2str($this->titulo)
                . ", texto = " . $this->var2str($this->texto)
                . ", resumen = " . $this->var2str($this->resumen)
                . ", fecha = " . $this->var2str($this->fecha)
                . ", publicada = " . $this->var2str($this->publicada)
                . ", codfuente = " . $this->var2str($this->codfuente)
                . ", likes = " . $this->var2str($this->likes)
                . ", tweets = " . $this->var2str($this->tweets)
                . ", meneos = " . $this->var2str($this->meneos)
                . ", popularidad = " . $this->var2str($this->popularidad)
                . ", keywords = " . $this->var2str($this->keywords)
                . ", preview = " . $this->var2str($this->preview)
                . ", editada = " . $this->var2str($this->editada)
                . ", destacada = " . $this->var2str($this->destacada)
                . ", nativa = " . $this->var2str($this->nativa)
                . ", parodia = " . $this->var2str($this->parodia)
                . ", id_relacionada = " . $this->var2str($this->id_relacionada)
                . ", meneame_link = " . $this->var2str($this->meneame_link)
                . ", permalink = " . $this->var2str($this->permalink)
                . "  WHERE id = " . $this->var2str($this->id) . ";";

            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO " . $this->table_name . " (url,titulo,texto,resumen,fecha,publicada"
                . ",codfuente,likes,tweets,meneos,popularidad,keywords,preview,editada,"
                . "destacada,nativa,parodia,id_relacionada,meneame_link,permalink) VALUES ("
                . $this->var2str($this->url) . ","
                . $this->var2str($this->titulo) . ","
                . $this->var2str($this->texto) . ","
                . $this->var2str($this->resumen) . ","
                . $this->var2str($this->fecha) . ","
                . $this->var2str($this->publicada) . ","
                . $this->var2str($this->codfuente) . ","
                . $this->var2str($this->likes) . ","
                . $this->var2str($this->tweets) . ","
                . $this->var2str($this->meneos) . ","
                . $this->var2str($this->popularidad) . ","
                . $this->var2str($this->keywords) . ","
                . $this->var2str($this->preview) . ","
                . $this->var2str($this->editada) . ","
                . $this->var2str($this->destacada) . ","
                . $this->var2str($this->nativa) . ","
                . $this->var2str($this->parodia) . ","
                . $this->var2str($this->id_relacionada) . ","
                . $this->var2str($this->meneame_link) . ","
                . $this->var2str($this->permalink) . ");";

            if ($this->db->exec($sql)) {
                $this->id = $this->db->lastval();
                return TRUE;
            } else {
                return FALSE;
            }
        }
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE id = " . $this->var2str($this->id) . ";");
    }

    /**
     * Devuelve un array con las últimas noticias.
     * @param type $offset
     * @param type $order
     * @return \inme_noticia_fuente
     */
    public function all($offset = 0, $order = 'fecha DESC')
    {
        $nlist = [];

        $data = $this->db->select_limit("SELECT * FROM " . $this->table_name . " ORDER BY " . $order, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d) {
                $nlist[] = new inme_noticia_fuente($d);
            }
        }

        return $nlist;
    }

    public function all_from_fuente($codfuente, $offset = 0)
    {
        $nlist = [];
        $sql = "SELECT * FROM " . $this->table_name . " WHERE codfuente = " . $this->var2str($codfuente)
            . " ORDER BY fecha DESC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d) {
                $nlist[] = new inme_noticia_fuente($d);
            }
        }

        return $nlist;
    }

    public function all_from_keyword($key, $offset = 0)
    {
        $nlist = [];
        $sql = "SELECT * FROM " . $this->table_name . " WHERE keywords LIKE '%[" . $key . "]%'"
            . " ORDER BY popularidad DESC";

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d) {
                $nlist[] = new inme_noticia_fuente($d);
            }
        }

        return $nlist;
    }

    /**
     * Devuelve un array con las noticias que coinciden con la bueda.
     * @param type $query
     * @param type $offset
     * @return \inme_noticia_fuente
     */
    public function search($query, $offset = 0, $order = 'popularidad DESC')
    {
        $nlist = [];
        $query = $this->no_html(mb_strtolower($query, 'UTF8'));
        $sql = "SELECT * FROM " . $this->table_name . " WHERE lower(titulo) LIKE '%" . $query . "%'"
            . " OR lower(resumen) LIKE '%" . $query . "%'"
            . " ORDER BY " . $order;

        $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d) {
                $nlist[] = new inme_noticia_fuente($d);
            }
        }

        return $nlist;
    }
}
