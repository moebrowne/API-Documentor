<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Documentor</title>
    <link href="prism/prism.css" rel="stylesheet" />
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
</head>
<body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<div class="container">
    <h1>API Documentor <small>v0.1</small></h1>
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

?>


<?=  buildOptions($endpointStructure); ?>

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

if(empty($endpointDetails) === false) : ?>
    <div class="row">
        <div class="col-sm-12">
            <pre class="language-none no-pre" style="font-size: 110%;">
                <code>
                    <span class="token property"><?= $endpointDetails['method']; ?></span>
                    <?= preg_replace('/{(.+)}/', '<span class="token keyword">{$1}</span>', $endpointDetails['path']); ?>
                    ?
                    <?php foreach ($endpointDetails['parameters'] as $parameterName => $parameterDetails) : ?>
                        <span class="token keyword"><?= $parameterName; ?></span>=<span class="token regex"><?= $parameterDetails['regex']; ?></span>
                        <?= (end($endpointDetails['parameters']) !== $endpointDetails['parameters'][$parameterName]) ? '&':''; ?>
                    <?php endforeach; ?>

                </code>
            </pre>
        </div>
    </div>
<?php endif; ?>

    <div class="page-header">
        <h2>Arguments</h2>
    </div>

    <div class="row">
        <?php

        foreach ($endpointDetails['arguments'] as $argumentName => $argumentDetails) {
            ?>
            <div class="col-sm-3">
                <ul class="list-group">
                    <li class="list-group-item active">
                        <?= $argumentName; ?>
                        <span class="badge">Required</span>
                    </li>
                    <?php if (array_key_exists('regex', $argumentDetails)) : ?>
                        <li class="list-group-item">
                            Pattern: <?= $argumentDetails['regex']; ?>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php
        }
        ?>
    </div>


    <div class="page-header">
        <h2>Parameters</h2>
    </div>

    <div class="row">
    <?php

    foreach ($endpointDetails['parameters'] as $parameterName => $parameterDetails) {
        ?>
        <div class="col-sm-3">
            <ul class="list-group">
                <li class="list-group-item active">
                    <?= $parameterName; ?>=<span class="token regex"><?= $parameterDetails['regex']; ?></span>
                    <?php if($parameterDetails['required'] === true) : ?>
                        <span class="badge">Required</span>
                    <?php endif; ?>
                </li>
            <?php if (array_key_exists('default', $parameterDetails)) : ?>
                <li class="list-group-item">
                    Default: <?= var_export($parameterDetails['default']); ?>
                </li>
            <?php endif; ?>
            </ul>
        </div>
        <?php
    }
    ?>
    </div>

</div>

<script src="prism/prism.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body>
</html>