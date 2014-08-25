<?php

/*  Copyright 2014 Matti Lattu

    This file is part of Digabi Skaba
 
    Digabi Skaba is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This file contains settings for Digabi Skaba plugin. Some settings contain
 * i18n translations and thus they are set after the i18n is loaded.
 */

$DIGABI_SKABA_SOMEVALUE = NULL;
$DIGABI_SKABA_POST_AUTHOR_ID = NULL;
$DIGABI_SKABA_POST_CATEGORIES = Array();
$DIGABI_SKABA_BLUGA_APIKEY = NULL;
$DIGABI_SKABA_BLUGA_CACHE = NULL;
$DIGABI_SKABA_BLUGA_USERID = NULL;
$DIGABI_SKABA_CSS = NULL;

/**
 * Sets Digabi Skaba global variables (settings).
 * @global string $DIGABI_SKABA_SOMEVALUE
 * @global int $DIGABI_SKABA_POST_AUTHOR_ID
 * @global array $DIGABI_SKABA_POST_CATEGORIES
 * @global string $DIGABI_SKABA_CSS
 */
function digabi_skaba_set_global_settings () {
    global $DIGABI_SKABA_POST_AUTHOR_ID;
    global $DIGABI_SKABA_POST_CATEGORIES;
    global $DIGABI_SKABA_BLUGA_APIKEY;
    global $DIGABI_SKABA_BLUGA_CACHE;
    global $DIGABI_SKABA_BLUGA_USERID;
    global $DIGABI_SKABA_CSS;
    
    /**
     * All Digabi Skaba posts will be posted by this ID.
     * @global int $DIGABI_SKABA_POST_AUTHOR_ID
     */
    $DIGABI_SKABA_POST_AUTHOR_ID = 1;
    
    /**
     * Categories for Digabi Skaba posts.
     * @global array $DIGABI_SKABA_POST_CATEGORIES
     */
    $DIGABI_SKABA_POST_CATEGORIES = Array(2,3);
    
    /**
     * Bluga user ID for web page thumbnail generation. See digabi_skaba_get_bluga_thumbnail()
     * for more information.
     * @global string $DIGABI_SKABA_BLUGA_USERID
     */
    $DIGABI_SKABA_BLUGA_USERID = 0;
    
    /**
     * Bluga APIkey for web page thumbnail generation. See digabi_skaba_get_bluga_thumbnail()
     * for more information.
     * @global string $DIGABI_SKABA_BLUGA_APIKEY
     */
    $DIGABI_SKABA_BLUGA_APIKEY = 'replace_with_your_apikey';
    
    /**
     * Cache value (in days) for thumbnail generation. See digabi_skaba_get_bluga_thumbnail()
     * for more information.
     * @global int $DIGABI_SKABA_BLUGA_CACHE
     */
    $DIGABI_SKABA_BLUGA_CACHE = 1;
    
    /**
     * CSS definitions for Digabi Skaba form. It may define following
     * styles used in the form:
     * digabi_skaba_cell_left - table cells, left column
     * digabi_skaba_cell_right - table cells, right column
     * digabi_skaba_cell_wide - wide cells (spanning from left to right)
     * digabi_skaba_legend - legend strings
     * 
     * We use inline CSS instead of wp_enqueue_style() to avoid loading Digabi
     * Skaba CSS for all pages.
     * @global string $DIGABI_SKABA_CSS
     */
    $DIGABI_SKABA_CSS = '<style>'
            .'.digabi_skaba_cell_left { color:red; } '
            .'.digabi_skaba_cell_right { color: green; } '
            .'.digabi_skaba_cell_wide { color: blue; } '
            .'.digabi_skaba_legend { font-style: italic; } '
            .'</style>';
 
}

?>
