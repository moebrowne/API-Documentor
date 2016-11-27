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

function buildOptions($options, $parentPath = null, $depth = 0)
{
    global $jsonPath;

    // If there are no options there is no need to continue
    if (is_array($options) === false || count($options) === 0) {
        return;
    }

    $selectOptions = [];
    $selectNextLevel = '';
    $selectedOption = '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 0;">Select <span class="caret"></span></button>';

    foreach ($options as $key => $option) {

        $path = !is_null($parentPath) ? $parentPath . '.' . $key:$key;

        if (isset($jsonPath[$depth]) && $jsonPath[$depth] === $key) {
            $selectNextLevel = buildOptions($option, $path, ++$depth);
            $selectedOption = '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="border-radius: 0;">'.$key.' <span class="caret"></span></button>';
            //continue;
        }

        $selectOptions[] = '<li><a href="?path=' . $path .'">'.$key.'</a></li>';
    }

    $options = implode('', $selectOptions);

    return <<<HTML
    <div class="dropdown" style="float: left;">
        {$selectedOption}
        <ul class="dropdown-menu">
            {$options}
        </ul>
    </div>
    {$selectNextLevel}
HTML;
}

?>


<div class="row">
    <?= buildOptions($endpointStructure); ?>
</div>

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
                    <?php if (array_key_exists('parameters', $endpointDetails) && count($endpointDetails['parameters']) > 0) : ?>
                        ?
                        <?php foreach ($endpointDetails['parameters'] as $parameterName => $parameterDetails) : ?>
                            <span class="token keyword"><?= $parameterName; ?></span>=<span class="token regex"><?= $parameterDetails['regex']; ?></span>
                            <?= (end($endpointDetails['parameters']) !== $endpointDetails['parameters'][$parameterName]) ? '&':''; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </code>
            </pre>
        </div>
    </div>
<?php endif; ?>

<?php if(array_key_exists('arguments', $endpointDetails) && count($endpointDetails['arguments']) > 0) : ?>

    <div class="row">
        <div class="col-md-12">
            <h2>Path Parameters</h2>

            <table class="table">
            <?php foreach ($endpointDetails['arguments'] as $argumentName => $argumentDetails) : ?>
                <tr>
                    <th width="20%">
                        <?= $argumentName; ?>
                        <?php if($argumentDetails['required'] !== false) : ?>
                            <span style="color: #AD0000; font-size: 20px;">*</span>
                        <?php endif; ?>
                    </th>
                    <td width="22%">
                        <small style="color: #999;">Type:</small><br>
                        <strong class="token <?= $argumentDetails['type']; ?>"><?= $argumentDetails['type']; ?></strong>
                    </td>
                    <td>
                        <?php if (!empty($argumentDetails['description'])) : ?>
                            <?= $argumentDetails['description']; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>
        </div>
    </div>
<?php endif; ?>


<?php if(array_key_exists('parameters', $endpointDetails) && count($endpointDetails['parameters']) > 0) : ?>

    <div class="row">
        <div class="col-md-12">
            <h2>Query String Parameters</h2>

            <table class="table">
            <?php foreach ($endpointDetails['parameters'] as $parameterName => $parameterDetails) : ?>
                <tr>
                    <th width="20%">
                        <?= $parameterName; ?>
                        <?php if($parameterDetails['required'] === true) : ?>
                            <span style="color: #AD0000; font-size: 20px;">*</span>
                        <?php endif; ?>
                    </th>
                    <td width="11%">
                        <small style="color: #999;">Type:</small><br>
                        <strong class="token <?= $parameterDetails['type']; ?>"><?= $parameterDetails['type']; ?></strong>
                    </td>
                    <td width="11%">
                        <small style="color: #999;">Default:</small><br>
                        <strong>
                            <?php if ($parameterDetails['default'] === null) : ?>
                                <span style="color: #777; font-size: 12px; font-style: italic;">NULL</span>
                            <?php else : ?>
                                <?= $parameterDetails['default']; ?>
                            <?php endif; ?>
                        </strong>
                    </td>
                    <td>
                        <?php if (!empty($parameterDetails['description'])) : ?>
                            <?= $parameterDetails['description']; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </table>
        </div>
    </div>
<?php endif; ?>

</div>

<script src="prism/prism.js"></script>
<!-- Latest compiled and minified JavaScript -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
</body>
</html>