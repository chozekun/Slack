<?php

define('BLACK_LIST', array('\$this', 'self::', '_SESSION', '_SERVER', '_ENV', 'eval', 'exec', 'unlink', 'rmdir'));

define('TAG_REGEXP', array(
    'loop'          => '(\{loop(?: name){0,1}="\${0,1}[^"]*"\})',
    'break'	        => '(\{break\})',
    'loop_close'    => '(\{\/loop\})',
    'if'            => '(\{if(?: condition){0,1}="[^"]*"\})',
    'elseif'        => '(\{elseif(?: condition){0,1}="[^"]*"\})',
    'else'          => '(\{else\})',
    'if_close'      => '(\{\/if\})',
    'function'      => '(\{function="[^"]*"\})',
    'noparse'       => '(\{noparse\})',
    'noparse_close' => '(\{\/noparse\})',
    'ignore'        => '(\{ignore\}|\{\*)',
    'ignore_close'	=> '(\{\/ignore\}|\*\})',
));



function template_function_check($code)
{
    $preg = '#(\W|\s)' . implode('(\W|\s)|(\W|\s)', BLACK_LIST) . '(\W|\s)#';
    // check if the function is in the black list (or not in white list)
    if (count(BLACK_LIST) && preg_match($preg, $code, $match)) {
        // stop the execution of the script
        throw new Exception('Unallowed syntax in template');
    }
}

function template_const_replace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null)
{
    return preg_replace('/\{\#(\w+)\#{0,1}\}/', $php_left_delimiter . ($echo ? " echo " : null) . '\\1' . $php_right_delimiter, $html);
}

// replace functions/modifiers on constants and strings
function template_func_replace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null)
{
    preg_match_all('/' . '\{\#{0,1}(\"{0,1}.*?\"{0,1})(\|\w.*?)\#{0,1}\}' . '/', $html, $matches);

    for ($i = 0, $n = count($matches[0]); $i < $n; $i++) {

        // complete tag ex: {$news.title|substr:0,100}
        $tag = $matches[ 0 ][ $i ];

        // variable name ex: news.title
        $var = $matches[ 1 ][ $i ];

        // function and parameters associate to the variable ex: substr:0,100
        $extra_var = $matches[ 2 ][ $i ];

        // check if there's any function disabled by black_list
        template_function_check($tag);

        $extra_var = template_var_replace($extra_var, null, null, null, null, $loop_level);


        // check if there's an operator = in the variable tags, if there's this is an initialization so it will not output any value
        $is_init_variable = preg_match("/^(\s*?)\=[^=](.*?)$/", $extra_var);

        // function associate to variable
        $function_var = ($extra_var and $extra_var[0] == '|') ? substr($extra_var, 1) : null;

        // variable path split array (ex. $news.title o $news[title]) or object (ex. $news->title)
        $temp = preg_split("/\.|\[|\-\>/", $var);

        // variable name
        $var_name = $temp[ 0 ];

        // variable path
        $variable_path = substr($var, strlen($var_name));

        // parentesis transform [ e ] in [" e in "]
        $variable_path = str_replace('[', '["', $variable_path);
        $variable_path = str_replace(']', '"]', $variable_path);

        // transform .$variable in ["$variable"]
        $variable_path = preg_replace('/\.\$(\w+)/', '["$\\1"]', $variable_path);

        // transform [variable] in ["variable"]
        $variable_path = preg_replace('/\.(\w+)/', '["\\1"]', $variable_path);

        // if there's a function
        if ($function_var) {

            // check if there's a function or a static method and separate, function by parameters
            $function_var = str_replace("::", "@double_dot@", $function_var);

            // get the position of the first :
            if ($dot_position = strpos($function_var, ":")) {

                // get the function and the parameters
                $function = substr($function_var, 0, $dot_position);
                $params = substr($function_var, $dot_position + 1);

            } else {

                // get the function
                $function = str_replace("@double_dot@", "::", $function_var);
                $params = null;

            }

            // replace back the @double_dot@ with ::
            $function = str_replace("@double_dot@", "::", $function);
            $params = str_replace("@double_dot@", "::", $params);


        } else {
            $function = $params = null;
        }

        $php_var = $var_name . $variable_path;

        // compile the variable for php
        if (isset($function)) {
            if ($php_var) {
                $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . ($params ? "( $function( $php_var, $params ) )" : "$function( $php_var )") . $php_right_delimiter;
            } else {
                $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . ($params ? "( $function( $params ) )" : "$function()") . $php_right_delimiter;
            }
        } else {
            $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . $php_var . $extra_var . $php_right_delimiter;
        }

        $html = str_replace($tag, $php_var, $html);

    }

    return $html;
}

function template_var_replace($html, $tag_left_delimiter, $tag_right_delimiter, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level = null, $echo = null)
{
    // all variables
    if (preg_match_all('/' . $tag_left_delimiter . '\$(\w+(?:\.\${0,1}[A-Za-z0-9_]+)*(?:(?:\[\${0,1}[A-Za-z0-9_]+\])|(?:\-\>\${0,1}[A-Za-z0-9_]+))*)(.*?)' . $tag_right_delimiter . '/', $html, $matches)) {

        for ($parsed = array(), $i = 0, $n = count($matches[0]); $i < $n; $i++) {
            $parsed[$matches[0][$i]] = array('var' => $matches[1][$i], 'extra_var' => $matches[2][$i]);
        }

        foreach ($parsed as $tag => $array) {

            // variable name ex: news.title
            $var = $array['var'];

            // function and parameters associate to the variable ex: substr:0,100
            $extra_var = $array['extra_var'];

            // check if there's any function disabled by black_list
            template_function_check($tag);

            $extra_var = template_var_replace($extra_var, null, null, null, null, $loop_level);

            // check if there's an operator = in the variable tags, if there's this is an initialization so it will not output any value
            $is_init_variable = preg_match("/^[a-z_A-Z\.\[\](\-\>)]*=[^=](.*?)$/", $extra_var);

            // function associate to variable
            $function_var = ($extra_var and $extra_var[0] == '|') ? substr($extra_var, 1) : null;

            // variable path split array (ex. $news.title o $news[title]) or object (ex. $news->title)
            $temp = preg_split("/\.|\[|\-\>/", $var);

            // variable name
            $var_name = $temp[ 0 ];

            // variable path
            $variable_path = substr($var, strlen($var_name));

            // parentesis transform [ e ] in [" e in "]
            $variable_path = str_replace('[', '["', $variable_path);
            $variable_path = str_replace(']', '"]', $variable_path);

            // transform .$variable in ["$variable"] and .variable in ["variable"]
            $variable_path = preg_replace('/\.(\${0,1}\w+)/', '["\\1"]', $variable_path);

            // if is an assignment also assign the variable to $this->var['value']
            if ($is_init_variable) {
                $extra_var = "{$variable_path}" . $extra_var;
            }



            // if there's a function
            if ($function_var) {

                // check if there's a function or a static method and separate, function by parameters
                $function_var = str_replace("::", "@double_dot@", $function_var);


                // get the position of the first :
                if ($dot_position = strpos($function_var, ":")) {

                    // get the function and the parameters
                    $function = substr($function_var, 0, $dot_position);
                    $params = substr($function_var, $dot_position + 1);

                } else {

                    // get the function
                    $function = str_replace("@double_dot@", "::", $function_var);
                    $params = null;

                }

                // replace back the @double_dot@ with ::
                $function = str_replace("@double_dot@", "::", $function);
                $params = str_replace("@double_dot@", "::", $params);
            } else {
                $function = $params = null;
            }

            // if it is inside a loop
            if ($loop_level) {
                // verify the variable name
                if ($var_name == 'key') {
                    $php_var = '$key' . $loop_level;
                } elseif ($var_name == 'value') {
                    $php_var = '$value' . $loop_level . $variable_path;
                } elseif ($var_name == 'counter') {
                    $php_var = '$counter' . $loop_level;
                } else {
                    $php_var = '$' . $var_name . $variable_path;
                }
            } else {
                $php_var = '$' . $var_name . $variable_path;
            }

            // compile the variable for php
            if (isset($function)) {
                $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . ($params ? "( $function( $php_var, $params ) )" : "$function( $php_var )") . $php_right_delimiter;
            } else {
                $php_var = $php_left_delimiter . (!$is_init_variable && $echo ? 'echo ' : null) . $php_var . $extra_var . $php_right_delimiter;
            }

            $html = str_replace($tag, $php_var, $html);


        }
    }

    return $html;
}

function template_tokenize($template_code)
{
    $tag_regexp = "/" . join("|", TAG_REGEXP) . "/";

    // split the code with the tags regexp
    return preg_split($tag_regexp, $template_code, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
}

function template_compile_code($tokenized_template)
{
    // if parsed code is empty return null string
    if (!$tokenized_template) {
        return "";
    }

    // variables initialization
    $comment_is_open = $ignore_is_open = $ignore_next_newline = false;
    $open_if = $loop_level = 0;
    $compiled_code = "";

    // read all parsed code
    foreach ($tokenized_template as $html) {

        // close ignore tag
        if (!$comment_is_open && (strpos($html, '{/ignore}') !== false || strpos($html, '*}') !== false)) {
            $ignore_is_open = false;
        }

        // code between tag ignore id deleted
        elseif ($ignore_is_open) {
            // ignore the code
        }

        // close no parse tag
        elseif (strpos($html, '{/noparse}') !== false) {
            $comment_is_open = false;
        }

        // code between tag noparse is not compiled
        elseif ($comment_is_open) {
            $compiled_code .= $html;
        }

        // ignore
        elseif (strpos($html, '{ignore}') !== false || strpos($html, '{*') !== false) {
            $ignore_is_open = true;
        }

        // noparse
        elseif (strpos($html, '{noparse}') !== false) {
            $comment_is_open = true;
        }

        // loop
        elseif (preg_match('/\{loop(?: name){0,1}="\${0,1}([^"]*)"\}/', $html, $code)) {

            // increase the loop counter
            $loop_level++;

            // replace the variable in the loop
            $var = template_var_replace('$' . $code[ 1 ], $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level - 1);

            // loop variables
            $counter = "\$counter$loop_level";       // count iteration
            $key = "\$key$loop_level";               // key
            $value = "\$value$loop_level";           // value

            // loop code
            $compiled_code .=  "<?php $counter=-1; if( !is_null($var) && is_array($var) && sizeof($var) ) foreach( $var as $key => $value ){ $counter++; ?>";

            $ignore_next_newline = true;
        }

        // loop break
        elseif (strpos($html, '{break}') !== false) {

            // else code
            $compiled_code .=   '<?php break; ?>';

            $ignore_next_newline = true;
        }

        // close loop tag
        elseif (strpos($html, '{/loop}') !== false) {

            // iterator
            $counter = "\$counter$loop_level";

            // decrease the loop counter
            $loop_level--;

            // close loop code
            $compiled_code .=  "<?php } ?>";

            $ignore_next_newline = true;
        }

        // if
        elseif (preg_match('/\{if(?: condition){0,1}="([^"]*)"\}/', $html, $code)) {

            // increase open if counter (for intendation)
            $open_if++;

            // tag
            $tag = $code[ 0 ];

            // condition attribute
            $condition = $code[ 1 ];

            // check if there's any function disabled by black_list
            template_function_check($tag);

            // variable substitution into condition (no delimiter into the condition)
            $parsed_condition = template_var_replace($condition, $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level);

            // if code
            $compiled_code .=   "<?php if( $parsed_condition ){ ?>";

            $ignore_next_newline = true;
        }

        // elseif
        elseif (preg_match('/\{elseif(?: condition){0,1}="([^"]*)"\}/', $html, $code)) {

            // tag
            $tag = $code[ 0 ];

            // condition attribute
            $condition = $code[ 1 ];

            // variable substitution into condition (no delimiter into the condition)
            $parsed_condition = template_var_replace($condition, $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level);

            // elseif code
            $compiled_code .=   "<?php }elseif( $parsed_condition ){ ?>";

            $ignore_next_newline = true;
        }

        // else
        elseif (strpos($html, '{else}') !== false) {

            // else code
            $compiled_code .=   '<?php }else{ ?>';

            $ignore_next_newline = true;
        }

        // close if tag
        elseif (strpos($html, '{/if}') !== false) {

            // decrease if counter
            $open_if--;

            // close if code
            $compiled_code .=   '<?php } ?>';

            $ignore_next_newline = true;
        }

        // function
        elseif (preg_match('/\{function="(\w*)(.*?)"\}/', $html, $code)) {

            // tag
            $tag = $code[ 0 ];

            // function
            $function = $code[ 1 ];

            // check if there's any function disabled by black_list
            template_function_check($tag);

            if (empty($code[ 2 ])) {
                $parsed_function = $function . "()";
            } else { // parse the function
                $parsed_function = $function . template_var_replace($code[ 2 ], $tag_left_delimiter = null, $tag_right_delimiter = null, $php_left_delimiter = null, $php_right_delimiter = null, $loop_level);
            }

            // if code
            $compiled_code .=   "<?php echo $parsed_function; ?>";
        }

        // all html code
        else {
            if ($ignore_next_newline) {
                $html = ltrim($html, "\n\r");
                $ignore_next_newline = false;
            }

            // variables substitution (es. {$title})
            $html = template_var_replace($html, $left_delimiter = '\{', $right_delimiter = '\}', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $loop_level, $echo = true);
            // const substitution (es. {#CONST#})
            $html = template_const_replace($html, $left_delimiter = '\{', $right_delimiter = '\}', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $loop_level, $echo = true);
            // functions substitution (es. {"string"|functions})
            $compiled_code .= template_func_replace($html, $left_delimiter = '\{', $right_delimiter = '\}', $php_left_delimiter = '<?php ', $php_right_delimiter = ';?>', $loop_level, $echo = true);
        }
    }

    if ($open_if > 0) {
        throw new Exception('Error! You need to close an {if} tag in template');
    }
    if ($loop_level > 0) {
        throw new Exception('Error! You need to close an {loop} tag in template');
    }
    return $compiled_code;
}

function template_compile($template_code)
{
    // xml substitution
    $template_code = preg_replace("/<\?xml(.*?)\?>/s", "##XML\\1XML##", $template_code);

    // disable php tag
    $template_code = str_replace(array("<?","?>"), array("&lt;?","?&gt;"), $template_code);

    // xml re-substitution
    $template_code = preg_replace_callback(
        "/##XML(.*?)XML##/s",
        function ($capture) {
            return "<?php echo '<?xml ".stripslashes($capture[1])." ?>'; ?>";
        },
        $template_code
    );

    $tokenized_template = template_tokenize($template_code);

    try {
        $template_compiled = template_compile_code($tokenized_template);
    } catch (Throwable $e) {
        $message = $e->getMessage();
        return "<?php echo '$message';";
    }

    // fix the php-eating-newline-after-closing-tag-problem
    $template_compiled = str_replace("?>\n", "?>\n\n", $template_compiled);

    return $template_compiled;
}

function template_render($compiled_code, $vars)
{
    // INFO: This is because newline processing in eval cannot be done properly if there is a \r character
    $compiled_code = str_replace("\r", "\n", $compiled_code);
    ob_start();
    extract($vars);
    try {
        eval('?>' . $compiled_code);
    } catch (Throwable $e) {
        ob_end_clean();
        $message = $e->getMessage();
        return "$message";
    }
    $contents = ob_get_clean();
    return $contents;
}
