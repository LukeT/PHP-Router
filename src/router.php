<?php
/*
 *
 * The MIT License (MIT)
 * Copyright (c) 2014 Luke Thompson

 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:

 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 */

class Router
{

    protected static $routes;
    protected static $errormsg;

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
        else if (isset($array['function']) && !empty($array['function']))
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
        if(isset(self::$errormsg) && !empty(self::$errormsg))
        {
            throw new Exception("No route defined.");
        }
        else
        {
            echo htmlentities(self::$errormsg);
        }
    }

    /**
     * Detect route handler, and push it to the required function
     *
     * @param $routeData
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
     * @param $routeData
     * @return array|bool
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
     * @param $routeData
     * @return array
     */
    private static function doFunction($routeData)
    {

        if(function_exists($routeData['route']['action']))
        {

            ob_start();
            $o = call_user_func_array($routeData['route']['action'], $routeData['dataMatches']);
            $oc = ob_get_contents();
            ob_end_clean();

            return ["return" => $o, "echo" => $oc];

        }
    }

    /**
     * Set the error message
     *
     * @param $string
     */
    public function unknown($string)
    {
        if(is_string($string))
        {
            self::$errormsg = $string;
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

        echo $proc['echo'];

        if(!is_bool($proc['return']))
        {
            echo $proc['return'];
        }

    }
}
