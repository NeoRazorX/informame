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
 * Description of social_share_count
 *
 * @author carlos
 */
class social_share_count
{

    /**
     * 
     * @param string $link
     * @return int
     */
    public function get_count($link)
    {
        if (empty($url)) {
            return 0;
        }

        switch (mt_rand(0, 3)) {
            case 0:
                return $this->get_facebook_count($link);

            case 1:
                return $this->get_meneame_count($link);

            case 2:
                return $this->get_mercenie_count($link);

            default:
                return $this->get_sharethis_count($link);
        }
    }

    /**
     * 
     * @param string $link
     * @return int
     */
    private function get_facebook_count($link)
    {
        $json_string = fs_file_get_contents('http://graph.facebook.com/?id=' . rawurlencode($link));
        $json = json_decode($json_string, TRUE);

        if (isset($json['share']['share_count'])) {
            return (int) $json['share']['share_count'];
        }

        return 0;
    }

    /**
     * 
     * @param string $link
     * @return int
     */
    private function get_meneame_count($link)
    {
        $string = fs_file_get_contents('http://www.meneame.net/api/url.php?url=' . rawurlencode($link));
        $vars = explode(' ', $string);

        return (count($vars) == 4) ? (int) $vars[2] : 0;
    }

    /**
     * 
     * @param string $link
     * @return int
     */
    private function get_mercenie_count($link)
    {
        $json_string = fs_file_get_contents('http://tools.mercenie.com/social-share-count/api/?flag=255&format=json&url=' . rawurlencode($link));
        $json = json_decode($json_string, TRUE);
        if (empty($json)) {
            return 0;
        }

        $count = 0;
        foreach ($json as $line) {
            foreach (['count', 'plusones', 'total_count', 'likes', 'ups'] as $field) {
                if (isset($line[$field])) {
                    $count += (int) $line[$field];
                    break;
                }
            }
        }

        return $count;
    }

    /**
     * 
     * @param string $link
     * @return int
     */
    private function get_sharethis_count($link)
    {
        $json_string = fs_file_get_contents('http://count-server.sharethis.com/v2.0/get_counts?url=' . rawurlencode($link));
        $json = json_decode($json_string, TRUE);

        if (isset($json['total'])) {
            return (int) $json['total'];
        }

        return 0;
    }
}
