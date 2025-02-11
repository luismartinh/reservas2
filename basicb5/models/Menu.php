<?php

namespace app\models;

use \app\models\base\Menu as BaseMenu;
use yii\helpers\Url;

/**
 * This is the model class for table "menu".
 */
class Menu extends BaseMenu
{


    private static function _getItems2($label, $items)
    {

        $acitems = null;

        foreach ($items as $item) {
            $acitems .= $item;
        }

        $me = <<<HTML
                        <!-- Dropdown anidado -->
                        <li class="dropend">
                            <a class="dropdown-item dropdown-toggle" href="#" id="nestedDropdown" role="button" data-bs-toggle="dropdown">
                                $label
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="nestedDropdown">
                                $acitems
                            </ul>
                        </li>
        HTML;

        return $me;

    }



    /**
     * @param integer $rootMenuKey
     * @param Identificador $user
     * 
     * @return string
     */

    private static function _getItems1($rootMenuKey, $user)
    {

        $items = null;


        $id_menus = $user->accesosDisponibles()->select(['id_menu'])->distinct()->asArray()->all();

        // Extraer los valores de 'id_menu' como un array plano
        $id_menus_array = array_column($id_menus, 'id_menu');

        $ms = Menu::find()
            ->where(['menu' => $rootMenuKey])
            ->andWhere(['id' => $id_menus_array]) // Filtra por los valores de id_menu
            ->orderBy(['menu_path' => SORT_ASC])
            ->all();

        if(count($ms)==0){
            return '';
        }

        $sm1 = [];

        foreach ($ms as $msitem) {
            $it = null;
            if (str_contains($msitem->menu_path, "/")) {
                $submenues = explode("/", $msitem->menu_path);
                switch (count($submenues)) {

                    case 2:

                        if (!array_key_exists($submenues[0], $sm1)) {
                            $sm1[$submenues[0]] = [];
                        }

                        $label = $submenues[1];
                        $url = Url::to([$msitem->url]);
                        $url = "href=\"{$url}\"";

                        $itm = <<<HTML
                            <li><a class="dropdown-item" $url>$label</a></li>
                        HTML;

                        $sm1[$submenues[0]][] = $itm;

                        break;
                }



            } else {

                $label = $msitem->label;
                $url = Url::to([$msitem->url]);
                $url = "href=\"{$url}\"";

                $it = <<<HTML
                    <li><a class="dropdown-item" $url>$label</a></li>
                HTML;


            }


            $items .= $it;

        }


        foreach ($sm1 as $key => $value) {
            $items .= self::_getItems2($key, $value);
        }



        $me = <<<HTML
                    <ul class="dropdown-menu" aria-labelledby="mainDropdown">
                        $items
                    </ul>
        HTML;



        return $me;
    }


    /**
     * @param array|null $exclude
     * @param Identificador $user
     * 
     * @return string|null
     */

    public static function getMenu($exclude = null, $user)
    {
        $menus = null;



        $rootMenu = \app\config\RootMenu::getROOT();

        if (is_array($exclude)) {
            $rootMenu = array_diff_key($rootMenu, array_flip($exclude));
        }

        foreach ($rootMenu as $key => $value) {
            $menus .= self::getRootMenu($key, $user);
        }

        return $menus;


    }

    /**
     * @param integer $rootMenuKey
     * @param Identificador $user
     * 
     * @return string
     */

    public static function getRootMenu($rootMenuKey, $user)
    {

        $rootMenu = \app\config\RootMenu::getROOT();
        $label = $rootMenu[$rootMenuKey]["label"];
        $icon = $rootMenu[$rootMenuKey]["icon"];

        $submenu = self::_getItems1($rootMenuKey, $user);

        if($submenu=="") return '';

        $me = <<<HTML
            <ul class="navbar-nav">
                <!-- Dropdown principal -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="mainDropdown" role="button" data-bs-toggle="dropdown">
                    $icon    
                    $label      
                    </a>
                    $submenu
                </li>
                
            </ul>
        HTML;

        return $me;
    }

    public static function getcustomMenu($label, $icon, $yiiurl)
    {

        $url = Url::to([$yiiurl]);
        $url = "href=\"{$url}\"";


        $me = <<<HTML
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" $url>
                        $icon 
                        $label
                    </a>
                </li>	                   
            </ul>
        HTML;

        return $me;
    }

    public static function getColSeparator()
    {
        $col_separator = <<<HTML
            <li class="nav-item py-2 py-lg-1 col-12 col-lg-auto">
                <div class="vr d-none d-lg-flex h-100 mx-lg-2 text-white"></div>
                <hr class="d-lg-none my-2 text-white-50">
            </li>    
        HTML;

        return $col_separator;
    }


    public static function getDarkModeMenu()
    {
        $change_dark_mode = <<<HTML
            <li class="nav-item dropdown">
                    <button class="btn btn-link nav-link py-2 px-0 px-lg-2 dropdown-toggle d-flex align-items-center" id="bd-theme" type="button" 
                    aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Toggle theme (dark)">
                        <i class="bi bi-moon-stars-fill my-1 theme-icon-active"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme-text">
                         <li class="theme-menu-item" data-bs-theme-value="light">
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
                                <i class="bi bi-sun-fill me-2 opacity-50"></i>
                                Claro
                                <svg class="bi ms-auto d-none"><use href="#check2"></use></svg>
                            </button>
                        </li>
                        <li class="theme-menu-item" data-bs-theme-value="dark">
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                            <i class="bi bi-moon-stars-fill me-2 opacity-50"></i>
                            Oscuro
                            <svg class="bi ms-auto d-none"><use href="#check2"></use></svg>
                            </button>
                        </li>

                        <li class="theme-menu-item" data-bs-theme-value="auto">
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="auto" aria-pressed="false">
                                <i class="bi bi-circle-half me-2 opacity-50"></i>
                                Auto
                                <svg class="bi ms-auto d-none"><use href="#check2"></use></svg>
                            </button>
                        </li>

                    </ul>
                 </li>
        HTML;

        return $change_dark_mode;
    }



    public static function getNotificacionesMenu($yiiurl,$user)
    {

        $url = Url::to([$yiiurl]);
        $href = "href=\"{$url}\"";

        $cant=Notificaciones::getCantNoLeidas($user);

        $badge=<<<HTML
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            $cant
                        <span class="visually-hidden">unread messages</span>                        

        HTML;

        
        if($cant==0){
            $badge='';
        }


        $menu = <<<HTML

                <!-- Dropdown principal -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle py-2 px-0 px-lg-2 dropdown-toggle d-flex align-items-center" 
                        href="#" id="notificaciones" role="button" data-bs-toggle="dropdown">
                        <i class="bi-bell-fill my-1 theme-icon-active"></i>
                        $badge
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="mainDropdown">
                        <li><a class="dropdown-item" $href>Ver</a></li>
                    </ul>
                </li>
        HTML;

        return $menu;
    }

}
