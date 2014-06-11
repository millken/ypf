<?php

namespace Cat\Common;

class Router extends \Cat\Controller {
//from https://github.com/nikic/FastRoute/blob/master/src/RouteParser/Std.php
/**
* Parses routes of the following form:
*
* "/user/{name}/{id:[0-9]+}"
*/
    const VARIABLE_REGEX = <<<'REGEX'
~\{
\s* ([a-zA-Z][a-zA-Z0-9_]*) \s*
(?:
: \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
)?
\}~x
REGEX;
    const DEFAULT_DISPATCH_REGEX = '[^/]+';

    private function parse($route) {
        if (!preg_match_all(
            self::VARIABLE_REGEX, $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        )) {
            return array($route);
        }

        $offset = 0;
        $routeData = array();
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = array(
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX
            );
            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset != strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }


    public function index() {
        $route = \Ypf\Lib\Config::get("router.route");

        $path_info = $this->getUri();
        $url_segments = !empty($path_info) ? array_filter(explode('/',$path_info)) : array();
        $uri = implode('/',$url_segments);

        // Loop through the route array looking for wild-cards
        foreach ((array)$route as $key => $val) {
            // Convert wild-cards to RegEx
            $key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));
            echo $path_info . "=>" . $key .  "<br />";
            // Does the RegEx match?
            if (preg_match('#^'.$key.'$#', $uri)) {
				/*
                // Do we have a back-reference?
                if (strpos(@$val[1], '$') !== FALSE AND strpos($key, '(') !== FALSE) {
                    //get parameter
                    $val[1] = preg_replace('#^'.$key.'$#', $val, $uri);
                    parse_str($val[1], $url_parse);
                    $this->request->get = array_merge($this->request->get, $url_parse);
                    print_r( $val );
                   
                }*/
                $this->request->get['route'] = $val;
            }
            
        }
        //echo $this->request->get['route'];
		if (isset($this->request->get['route'])) {
			return $this->forward($this->request->get['route']);
		}
		echo 'xxxx';
    }

    private function getUri() {
        $uri = '';
        if(!empty($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        }elseif(isset($_SERVER['REQUEST_URI'])) {
            $uri = parse_url(str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['REQUEST_URI']), PHP_URL_PATH);
            $uri = rawurldecode($uri);
        }elseif (isset($_SERVER['PHP_SELF'])) {
            $uri = str_replace($_SERVER['SCRIPT_NAME'],'',$_SERVER['PHP_SELF']);
        }
        // Reduce multiple slashes to a single slash and Remove all dot-paths from the URI
        $uri = preg_replace(array('#\.[\s./]*/#','#//+#'), '/', $uri);
        return $uri;
    }
}
?>
