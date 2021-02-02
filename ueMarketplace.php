<?php
$dbname = 'ueMarketplace';
$dbuser = 'ueMarketplace';
$dbpass = 'F5mj?kDZUoJm';
$con = mysqli_connect('localhost',$dbuser,$dbpass,$dbname);
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$startTime = time();
$assetCount = 0;
$totalAssets = json_decode(file_get_contents('https://www.unrealengine.com/marketplace/api/assets?start=0&count=1'), true)['data']['paging']['total'];
$assetStart = 0;
if ($result = mysqli_query($con, 'SELECT * FROM ' . $dbname . '.vars LIMIT 1')) {
    if ($result->num_rows > 0) {
        $vars = $result->fetch_assoc();
        $assetStart = $vars['start'];
    }
} else {
    echo("Start: " . mysqli_error($con) . "<br>");
}

for ($start = $assetStart; $start <= $totalAssets; $start += 100) {
	$ueMarketplaceJSON = json_decode(file_get_contents('https://www.unrealengine.com/marketplace/api/assets?start=' . $start . '&count=100&sortDir=ASC'), true);
	foreach($ueMarketplaceJSON['data']['elements'] as $i => $ueMarketplaceAsset) {
        if (!mysqli_query($con, 'UPDATE ' . $dbname . '.vars SET start=' . ($start + intval($i)) . ' WHERE id=1')) {
            echo("Start (" . ($start + intval($i)) . "): " . mysqli_error($con) . "<br>");
        }
        if (time() - $startTime >= 298) {
            break;
        }
	    $assetid = $assetkeysinsert = $sellerkeysinsert = $pricekeysinsert = $assetvaluesinsert = $sellervaluesinsert = $pricevaluesinsert = $assetupdate = $sellerupdate = '';
        $assetprice = $assetdiscount = 0;
        foreach($ueMarketplaceAsset as $key => $val) {
            switch ($key) {
                case 'id':
                    $assetkeysinsert .= ',' . $key;
                    $pricekeysinsert .= ',' . $key;
                    $assetvaluesinsert .= ',"' . $val . '"';
                    $pricevaluesinsert .= ',"' . $val . '"';
                    $assetid = '"' . $val . '"';
                    break;
                case 'catalogItemId':
                    $assetkeysinsert .= ',' . $key;
                    $assetvaluesinsert .= ',"' . $val . '"';
                    break;
                case 'effectiveDate':
                    $assetkeysinsert .= ',' . $key;
                    $assetvaluesinsert .= ',"' . date("Y-m-d H:i:s", strtotime($val)) . '"';
                    $assetupdate .= ',' . $key . ' = "' . date("Y-m-d H:i:s", strtotime($val)) . '"';
                    break;
                case 'urlSlug':
                    $assetkeysinsert .= ',' . $key;
                    $assetvaluesinsert .= ',"' . $val . '"';
                    $assetupdate .= ',' . $key . ' = "' . $val . '"';
                    break;
                case 'title':
                case 'description':
                case 'technicalDetails':
                case 'longDescription':
                    $assetkeysinsert .= ',' . $key;
                    $assetvaluesinsert .= ',' . json_encode($val, JSON_UNESCAPED_UNICODE);
                    $assetupdate .= ',' . $key . ' = ' . json_encode($val, JSON_UNESCAPED_UNICODE);
                    break;
                case 'categories':
                    if (array_key_exists(0, $val)) {
                        $assetkeysinsert .= ',categoryPath,categoryName';
                        $assetvaluesinsert .= ',"' . $val[0]['path'] . '","' . $val[0]['name'] . '"';
                        $assetupdate .= ',categoryPath = "' . $val[0]['path'] . '",categoryName = "' . $val[0]['name'] . '"';
                    }
                    break;
                case 'releaseInfo':
                    if (count($val) > 0) {
                        $assetkeysinsert .= ',platforms';
                        $tempstring = '';
                        foreach($val[count($val) - 1]['platform'] as $k => $v) {
                            $tempstring .= ',' . $v;
                        }
                        $tempstring = substr($tempstring, 1);
                        $assetvaluesinsert .= ',"' . $tempstring . '"';
                        $assetupdate .= ',platforms = "' . $tempstring . '"';
                    }
                    break;
                case 'compatibleApps':
                    if (count($val) > 0) {
                        $assetkeysinsert .= ',compatibleApps';
                        $tempstring = '';
                        foreach($val as $k => $v) {
                            $tempstring .= ',' . $v;
                        }
                        $tempstring = substr($tempstring, 1);
                        $assetvaluesinsert .= ',"' . $tempstring . '"';
                        $assetupdate .= ',compatibleApps = "' . $tempstring . '"';
                    }
                    break;
                case 'tags':
                    if (count($val) > 0) {
                        $assetkeysinsert .= ',tags';
                        $tempstring = '';
                        if ($val != "") {
                            foreach($val as $k => $v) {
                                $tempstring .= ',' . $v;
                            }
                            $tempstring = substr($tempstring, 1);
                        }
                        $assetvaluesinsert .= ',"' . $tempstring . '"';
                        $assetupdate .= ',tags = "' . $tempstring . '"';
                    }
                    break;
                case 'rating':
                    $assetkeysinsert .= ',averageRating,totalVotes';
                    $assetvaluesinsert .= ',' . $val['averageRating'] . ',' . $val['total'];
                    $assetupdate .= ',averageRating = ' . $val['averageRating'] . ',totalVotes = ' . $val['total'];
                    break;
                case 'priceValue':
                    $pricekeysinsert .= ',price';
                    $pricevaluesinsert .= ',' . $val;
                    $assetprice = $val;
                    break;
                case 'discountPriceValue':
                    $pricekeysinsert .= ',discount';
                    $pricevaluesinsert .= ',' . $val;
                    $assetdiscount = $val;
                    break;
                case 'tax':
                    $pricekeysinsert .= ',' . $key;
                    $pricevaluesinsert .= ',' . $val;
                    break;
                case 'currencyCode':
                    $pricekeysinsert .= ',' . $key;
                    $pricevaluesinsert .= ',"' . $val . '"';
                    break;
                case 'seller':
                    foreach($val as $k => $v) {
                        switch ($k) {
                            case 'id':
                                $assetkeysinsert .= ',sellerId';
                                $assetvaluesinsert .= ',"' . $v . '"';
                                $sellerkeysinsert .= ',' . $k;
                                $sellervaluesinsert .= ',"' . $v . '"';
                                break;
                            case 'name':
                                $assetkeysinsert .= ',sellerName';
                                $assetvaluesinsert .= ',' . json_encode($v);
                                $assetupdate .= ',sellerName = ' . json_encode($v);
                                $sellerkeysinsert .= ',' . $k;
                                $sellervaluesinsert .= ',' . json_encode($v);
                                break;
                            case 'financeCheckExempted':
                                if ($v) {
                                    $sellerkeysinsert .= ',' . $k;
                                    $sellervaluesinsert .= ',1';
                                    $sellerupdate .= ',' . $k . ' = 1';
                                } else {
                                    $sellerkeysinsert .= ',' . $k;
                                    $sellervaluesinsert .= ',0';
                                    $sellerupdate .= ',' . $k . ' = 0';
                                }
                                break;
                            case 'status':
                                break;
                            case 'accepted':
                                break;
                            default:
                                $sellerkeysinsert .= ',' . $k;
                                $sellervaluesinsert .= ',' . json_encode($v);
                                $sellerupdate .= ',' . $k . ' = ' . json_encode($v);
                                break;
                        }
                    }
                    break;
                case 'keyImages':
                    foreach($val as $k => $v) {
                        $imagekeysinsert = ',id';
                        $imagevaluesinsert = ',' . $assetid;
                        foreach($v as $kk => $vv) {
                            $imagekeysinsert .= ',' . $kk;
                            $imagevaluesinsert .= ',' . json_encode($vv);
                        }
                        if (!mysqli_query($con, 'INSERT IGNORE INTO ' . $dbname . '.images (' . substr($imagekeysinsert, 1) . ') VALUES (' . substr($imagevaluesinsert, 1) . ')')) {
                            echo("Images (" . ($start + intval($i)) . "): " . mysqli_error($con) . "<br>");
                        }
                    }
                    break;
            }
        }
        $assetupdate .= ',scanDate=now()';
        
        if (!mysqli_query($con, 'INSERT INTO ' . $dbname . '.assets (' . substr($assetkeysinsert, 1) . ') VALUES (' . substr($assetvaluesinsert, 1) . ') ON DUPLICATE KEY UPDATE ' . substr($assetupdate, 1))) {
            echo("Asset (" . ($start + intval($i)) . "): " . mysqli_error($con) . "<br>");
        } else {
            $assetCount++;
        }
        $currentprice = $currentdiscount = -1;
        if ($result = mysqli_query($con, 'SELECT * FROM ' . $dbname . '.prices WHERE id=' . $assetid . ' ORDER BY date DESC LIMIT 1')) {
            if ($result->num_rows > 0) {
                $currentprices = $result->fetch_assoc();
                $currentprice = $currentprices['price'];
                $currentdiscount = $currentprices['discount'];
            }
        } else {
            echo("Get (" . ($start + intval($i)) . "): " . mysqli_error($con) . "<br>");
        }
        if ($currentprice != $assetprice || $currentdiscount != $assetdiscount) {
            if (!mysqli_query($con, 'INSERT INTO ' . $dbname . '.prices (' . substr($pricekeysinsert, 1) . ') VALUES (' . substr($pricevaluesinsert, 1) . ')')) {
                echo("Price (" . ($start + intval($i)) . "): " . mysqli_error($con) . "<br>");
            }
        }
        if (!mysqli_query($con, 'INSERT INTO ' . $dbname . '.sellers (' . substr($sellerkeysinsert, 1) . ') VALUES (' . substr($sellervaluesinsert, 1) . ') ON DUPLICATE KEY UPDATE ' . substr($sellerupdate, 1))) {
            echo("Seller (" . ($start + intval($i)) . "): " . mysqli_error($con) . "<br>");
        }
        
        if ($start + $i >= $totalAssets - 1) {
            $start = -100;
        }
    }
    //sleep(10);
    if (time() - $startTime >= 298) {
        break;
    }
}

error_log('Starting Index: ' . $assetStart . '<br>', 0);
error_log('Assets Scanned: ' . $assetCount . '<br>', 0);
error_log('Scan Time: ' . (time() - $startTime) . 's', 0);

mysqli_close($con);
?>
