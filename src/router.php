<?php

class Router
{

    protected static $routes;

    protected static $variables = [
        '[:string]' => '([a-zA-Z]+)',
        '[:int]' => '([0-9]+)',
        '[:alpha]'  => '([a-zA-Z0-9-_]+)',
    ];

    /**
     * Parse the route options
     *
     * @param $array
     *
     * @return array
     */
    public static function parseConfig($array)
    {
        $data = array();

        if(isset($array['controller']) && !empty($array['controller']))
        {
            $data['method'] = "controller";
            $data['action'] = $array['controller'];
        }
        else if ($array['function'])
        {
            $data['method'] = "function";
            $data['action'] = $array['controller'];
        }

        isset($array['name']) ? $data['name'] = $array['name'] : '';

        return $data;
    }

    /**
     * Handle a GET route
     *
     * @param $route
     * @param $array
     */
    public static function get($route, $array)
    {
        $options = self::parseConfig($array);

        self::$routes[$route] = array_merge(
            $options,
            [
                "type" => "GET",
                "path" => $route,
            ]
        );

    }

    /**
     * Handle a POST route
     *
     * @param $route
     * @param $array
     */
    public static function post($route, $array)
    {
        $options = self::parseConfig($array);

        self::$routes[$route] = array_merge(
            $options,
            [
                "type" => "POST",
                "path" => $route,
            ]
        );
    }

    /**
     * Get the current server URI
     *
     * @return string
     */
    public static function getURI()
    {
        if(!empty($_SERVER['REQUEST_URI']))
        {
            $path = $_SERVER['REQUEST_URI'];
        }
        else
        {
            $path = "/";
        }

        return $path;
    }

    /**
     * Look for a matching route.
     *
     * @param $currentURI
     * @return array
     */
    public static function compareRoute($currentURI)
    {
        $found_route = '';
        $dataMatches = array();

        if (isset(self::$routes[$currentURI]) && is_array(self::$routes[$currentURI])) {
            $found_route = self::$routes[$currentURI];
        }
        else if (is_array(self::$routes))
        {
            foreach (self::$routes as $uri => $dataset) {
                $regexURI = '|^/?' . strtr($uri, self::$variables) .'/?$|';

                if (preg_match($regexURI, $currentURI, $variables)) {
                    $found_route = self::$routes[$uri];
                    $dataMatches = $variables;

                    if($dataMatches[0] == $currentURI)
                    {
                        array_splice($dataMatches, 0, 1);
                    }
                    break;
                }
            }
        }

        return ["route" => $found_route, "dataMatches" => $dataMatches];
    }

    /**
     * Handle a 404 message
     *
     * @throws Exception
     */
    public static function do404()
    {
        throw new Exception("No route defined.");
    }
    
    /**
     * Carry out the routing process
     */
    public static function run()
    {
        $uri = self::getURI();;

        $route = self::compareRoute($uri);

        if(empty($route['route']))
        {
            self::do404();
        }

        //TODO: add handling code, parsing is done

    }
}