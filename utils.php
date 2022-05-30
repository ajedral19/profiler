<?php
declare(strict_types=1);

function extract_requests(?array $params = null): array
{
    $req_uri = explode('/', $_SERVER['REQUEST_URI']);
    $script_name = explode('/', $_SERVER['SCRIPT_NAME']);
    $arr = [];

    foreach ($req_uri as $p_key => $p_val) {
        if (isset($script_name[$p_key]) && $script_name[$p_key] === $p_val) {
            unset($req_uri[$p_key]);
        } else {
            $arr = [...$arr, $p_val];
        }
    }

    $_params = preg_replace('/\?/', '', preg_grep('/\?*=/', $arr));

    foreach ($_params as $key => $val) {
        unset($_params[$key]);
        list($k, $v) = preg_split('/\=/', $val);
        if (array_keys($params, $k)) {
            array_push($_params, [$k => $v]);
        }
    }

    return [...$_params, ...preg_grep('/\?*=/', $arr, PREG_GREP_INVERT)];
}

function generateId(string $prefix = 'user'): string
{
    $padLen = count(str_split($prefix)) + 4;
    if (empty($prefix)) {
        $padLen = 0;
    }
    $chars = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

    function makeSeed()
    {
        list($msec, $sec) = explode(' ', microtime());
        return intval($sec + $msec * 1000000);
    }

    function randomPrefix($chars, $padLen)
    {
        $res = '';
        for ($i = 0; $i < $padLen; $i += 1) {
            $rnd = mt_rand(0, count($chars) - 1);
            $res = $res . $chars[$rnd];
        }
        return $res;
    }

    mt_srand(makeSeed(), MT_RAND_MT19937);
    $generatedPrefix = randomPrefix($chars, $padLen);

    $prefix = str_pad(
        strtoupper($prefix),
        $padLen,
        $generatedPrefix,
        STR_PAD_RIGHT
    );

    return $prefix . mt_rand();
}

function set_response(
    bool $status,
    ?string $message = null,
    ?object $data = null,
    ?int $err = null
): object {
    $res = (object) [
        'success' => $status,
        'message' => $message,
        'row' => $data,
        'error' => $err,
    ];

    foreach ($res as $key => $value) {
        if (is_null($value)) {
            unset($res->$key);
        }
    }

    return $res;
}

function validate_fields(
    object $data,
    ?array $requiredProperties = null,
    ?array $consideredProperties = null
): ?string {
    if ($requiredProperties) {
        foreach ($requiredProperties as $key) {
            if (
                !isset($data->$key) ||
                (isset($data->$key) && empty($data->$key))
            ) {
                print json_encode(
                    set_response(false, "field $key has left empty.")
                );
                return $key;
            }
        }
    }
    if (!$requiredProperties) {
        foreach ($data as $key => $val) {
            if (empty($val)) {
                print json_encode(
                    set_response(false, "field $key has left empty.")
                );
                return $key;
            }
        }
    }
    return null;
}
