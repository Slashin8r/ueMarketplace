<?php
$dbname = 'ueMarketplace';
$dbuser = 'ueMarketplace';
$dbpass = 'F5mj?kDZUoJm';

if (array_key_exists('select', $_GET) && array_key_exists('db', $_GET) && $_GET['select'] == 'total') {
    $con = mysqli_connect('localhost',$dbuser,$dbpass,$dbname);
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    $totalrows = 0;
    if ($result = mysqli_query($con, 'SELECT * FROM ueMarketplace.' . $_GET['db'])) {
        if ($result->num_rows > 0) {
            $totalrows = $result->num_rows;
        }
    }
    $jsonresult = '{"total":' . $totalrows . '}';
    echo $jsonresult;
} else {
    $ueMarketplaceSQL = 'SELECT ';
    if (array_key_exists('select', $_GET)) {
        if ($_GET['select'] == 'all') {
            $ueMarketplaceSQL .= '* FROM ueMarketplace.';
        } else {
            $ueMarketplaceSQL .= str_replace(' ', ',', $_GET['select']);
            $ueMarketplaceSQL .= ' FROM ueMarketplace.';
        }
    } else {
        printf("select must be included");
        exit();
    }
    if (array_key_exists('db', $_GET)) {
        $ueMarketplaceSQL .= $_GET['db'];
    } else {
        printf("db must be included");
        exit();
    }
    if (array_key_exists('key', $_GET) && array_key_exists('value', $_GET)) {
        $ueMarketplaceSQL .= ' WHERE ' . $_GET['key'] . '=' . json_encode($_GET['value']);
    }
    if (array_key_exists('orderby', $_GET) && array_key_exists('sortdir', $_GET)) {
        $ueMarketplaceSQL .= ' ORDER BY ' . $_GET['orderby'] . ' ' . $_GET['sortdir'];
    }
    if (array_key_exists('start', $_GET)) {
        $ueMarketplaceSQL .= ' LIMIT ' . $_GET['start'];
    } else {
        $ueMarketplaceSQL .= ' LIMIT 0';
    }
    $countMax = 100;
    if ($_GET['select'] == 'all' || $_GET['db'] == 'images') {
        $countMax = 50;
    }
    if (array_key_exists('count', $_GET) && $_GET['count'] <= $countMax) {
        $ueMarketplaceSQL .= ',' . $_GET['count'];
    } else {
        $ueMarketplaceSQL .= ',' . $countMax;
    }
    
    $con = mysqli_connect('localhost',$dbuser,$dbpass,$dbname);
    if (mysqli_connect_errno()) {
        printf("Connect failed: %s\n", mysqli_connect_error());
        exit();
    }
    
    if ($result = mysqli_query($con, $ueMarketplaceSQL)) {
        if ($result->num_rows > 0) {
            $jsonresult = '{"assets":[';
            while ($ueMarketplaceAsset = $result->fetch_assoc()) {
                if ($jsonresult == '{"assets":[') {
                    $jsonresult .= '{';
                } else {
                    $jsonresult .= ',{';
                }
                $tempstring = '';
                foreach ($ueMarketplaceAsset as $key => $val) {
                    switch ($key) {
                        case 'num':
                        case 'width':
                        case 'height':
                        case 'size':
                        case 'financeCheckExempted':
                        case 'tax':
                            break;
                        case 'averageRating':
                        case 'totalVotes':
                            $tempstring .= ',"' . $key . '":' . $val;
                            break;
                        case 'id':
                            if ($_GET['db'] == 'assets') {
                                if ($priceresult = mysqli_query($con, 'SELECT price,discount,date FROM ueMarketplace.prices WHERE id="' . $val . '" ORDER BY date DESC')) {
                                    if ($priceresult->num_rows > 0) {
                                        $jsonprices = ',"prices":[';
                                        while ($ueMarketplaceAssetPrice = $priceresult->fetch_assoc()) {
                                            if ($jsonprices == ',"prices":[') {
                                                $jsonprices .= '{';
                                            } else {
                                                $jsonprices .= ',{';
                                            }
                                            $tempstringprices = '';
                                            foreach ($ueMarketplaceAssetPrice as $k => $v) {
                                                $tempstringprices .= ',"' . $k . '":' . json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                            }
                                            $jsonprices .= substr($tempstringprices, 1) . '}';
                                        }
                                        $jsonprices .= ']';
                                        $tempstring .= $jsonprices;
                                    }
                                }
                            }
                        default:
                            $tempstring .= ',"' . $key . '":' . json_encode($val, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                            break;
                    }
                }
                $jsonresult .= substr($tempstring, 1) . '}';
            }
            $jsonresult .= ']}';
            echo $jsonresult;
        }
    } else {
        echo("Key/Value: " . mysqli_error($con) . "<br>");
    }
}

mysqli_close($con);
?>
