<?php
/**
 * This file is part of FacturaSctipts
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
 * Description of inme_sitemap
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class inme_sitemap extends fs_controller
{

    public function __construct()
    {
        parent::__construct(__CLASS__, 'sitemap', 'comunidad', FALSE, FALSE);
    }

    protected function private_core()
    {
        $this->sitemap();
    }

    protected function public_core()
    {
        $this->sitemap();
    }

    private function sitemap()
    {
        $this->template = FALSE;

        $fsvar = new fs_var();
        $modrewrite = $fsvar->simple_get('inme_modrewrite');

        header("Content-type: text/xml");
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        $noti = new inme_noticia_fuente();
        foreach ($noti->all(0, 'editada DESC, publicada DESC') as $no) {
            if ($no->editada) {
                echo '<url><loc>', $this->empresa->web, '/', $no->url($modrewrite), '</loc><lastmod>',
                Date('Y-m-d', strtotime($no->fecha)), '</lastmod><changefreq>always</changefreq><priority>0.8</priority></url>';
            }
        }

        echo '</urlset>';
    }
}
