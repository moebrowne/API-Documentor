<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documentor</title>
</head>
<body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>

<?php

$endpointDefs = json_decode(file_get_contents('endpointDefs.json'), true);

$endpointStructure = [];

function dotToArray($arrayOfKeys) {
    $result = [];
    while (count($arrayOfKeys) > 0) {
        $result = array(array_pop($arrayOfKeys) => $result);
    }
    return $result;
}

foreach ($endpointDefs as $endpointSignature => $endpointDef) {
    $endpointSignatureArray = explode('.', $endpointSignature);
    $endpointStructure = array_merge_recursive($endpointStructure, dotToArray($endpointSignatureArray));
}


$jsonPath = array_filter(explode('.', $_GET['path']));

function buildOptions($options, $depth = 0)
{
    global $jsonPath;

    if (is_array($options) === false) {
        return;
    }

    $selectOptions = [];
    $selectNextLevel = '';

    foreach ($options as $key => $option) {
        $selected = '';

        if (isset($jsonPath[$depth]) && $jsonPath[$depth] === $key) {
            $selectNextLevel = buildOptions($option, ++$depth);
            $selected = ' selected';
        }

        $selectOptions[] = "<option value='".$key."' ".$selected.">".$key."</option>";
    }

    if (count($selectOptions) > 0) {
        array_unshift($selectOptions, "<option value=''>Select...</option>");
    }
    else {
        return;
    }

    return "<select id='".uniqid()."'>".implode('', $selectOptions)."</select>".$selectNextLevel;
}

echo buildOptions($endpointStructure);

?>

<script>
    $('select').on('change', function() {

        var path = '';

        var stopOn = $(this);

        $('select').each(function() {
            if ($(this).val() === '') {
                return false;
            }

            path += '.'+$(this).val();

            if ($(this).attr('id') === stopOn.attr('id')) {
                return false;
            }
        });

        console.log(path.substring(1));
        document.location.href = '?path='+path.substring(1);
    });
</script>
<hr>
<?php

$endpointDetails = $endpointDefs[$_GET['path']];
echo $endpointDetails['method'] . " " . $endpointDetails['path'];

foreach (array_keys($endpointDetails['parameters']) as $parameter) {
    echo "<br>PARAM: " . $parameter;
}

?>

</body>
</html>