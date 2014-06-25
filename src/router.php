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
    private static function parseConfig($array)
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
            $data['action'] = $array['function'];
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
    private static function getURI()
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
    private static function compareRoute($currentURI)
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
    private static function do404()
    {
        throw new Exception("No route defined.");
    }
    
    /**
     * Detect route handler, and push it to the required function
     *
     * @return function
     */
    private static function handleProcess($routeData)
    {
        if($routeData['route']['method'] == "controller")
        {
            return self::doController($routeData);
        }    
        else if($routeData['route']['method'] == "function")
        {
            return self::doFunction($routeData);
        }
        else
        {
            self::do404();
        }
    }

   /**
     * Call the controller defined in a route
     *
     * @return function
     */
    private static function doController($routeData)
    {
        $route = explode("@", $routeData['route']['action']);
        
        if(count($route) != 2)
        {
            return false;
        }
  
        if(class_exists($route[0]))
        {
            if(method_exists($route[0], $route[1]))
            {

                $helperClass = new $route[0]();
                ob_start();
                $o = call_user_func_array([$helperClass, $route[1]], $routeData['dataMatches']);
                $oc = ob_get_contents();
                ob_end_clean();

                if(empty($o) || is_null($o) || !$o)
                {
                    return false;
                }

                return ["return" => $o, "echo" => $oc];

            }
        }
    }

    /**
     * Call the function defined in a function
     *
     * @return function
     */
    private static function doFunction($routeData)
    {

        if(function_exists($routeData['route']['action']))
        {

            ob_start();
            $o = call_user_func($routeData['route']['action'], $routeData['dataMatches']);
            $oc = ob_get_contents();
            ob_end_clean();

            if(empty($o) || is_null($o) || !$o)
            {
                return false;
            }

            return ["return" => $o, "echo" => $oc];

        }
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
        else
        {
            $proc = self::handleProcess($route);
        }

        if(!$proc)
        {
            self::do404();
        }

        //TODO: Do somthing with the response + Implement real 404ings.

    }
}