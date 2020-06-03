function ueMarketplace()
{
  var priceArray = ["priceValue","discountPriceValue","discountPercentage","price","discount","discountPrice"];
  var removeKeys = ["keyImages","featured","thumbnail","learnThumbnail","headerImage","technicalDetails","longDescription"];
  var allAssetsJSON = $.ajax({async:false,global:false,url:"allAssets.json",dataType:"json",success:function (data) { return data; }}).responseJSON;
  var allAssetsRemovedKeysJSON = $.ajax({async:false,global:false,url:"allAssetsRemovedKeys.json",dataType:"json",success:function (data) { return data; }}).responseJSON;
  var totalAssetsJSON = $.ajax({async:false,global:false,url:"https://www.unrealengine.com/marketplace/api/assets?start=0&count=1",dataType:"json",success:function (data) { return data; }}).responseJSON;
  var totalAssets = totalAssetsJSON.data.paging.total;
  
  for (var start = 0; start < totalAssets; start += 100) {
    console.log(start);
    var ueMarketplaceJSON = $.ajax({async:false,global:false,url:"https://www.unrealengine.com/marketplace/api/assets?start="+start+"&count=100&sortDir=ASC",dataType:"json",success:function (data) { return data; }}).responseJSON;
    for (var i = 0; i < 100; i++) {
      if (start + i >= totalAssets) {
        break;
      }
      var ueMarketplaceAsset = ueMarketplaceJSON.data.elements[i];
      var foundAsset = findAssetByID(allAssetsJSON.assets, ueMarketplaceAsset.id, 0);
      if (foundAsset != -1) {
        for (var key = 0; key < priceArray.length; key++) {
          if (ueMarketplaceAsset.hasOwnProperty(priceArray[key])) {
            if (ueMarketplaceAsset[priceArray[key]] !== null) {
              if (ueMarketplaceAsset[priceArray[key]] != allAssetsJSON.assets[foundAsset][priceArray[key]][0].price) {
                allAssetsJSON.assets[foundAsset][priceArray[key]].unshift(JSON.parse('{"price":"'+ueMarketplaceAsset[priceArray[key]]+'","date":"'+new Date().toISOString()+'"}'));
              }
            }
          }
        }
        allAssetsJSON.assets[foundAsset].lastScan = new Date().toISOString();
      } else {
        for (var key = 0; key < priceArray.length; key++) {
          var newArray = [];
          if (ueMarketplaceAsset.hasOwnProperty(priceArray[key])) {
            if (ueMarketplaceAsset[priceArray[key]] !== null) {
              newArray.unshift(JSON.parse('{"price":"'+ueMarketplaceAsset[priceArray[key]]+'","date":"'+new Date().toISOString()+'"}'));
            }
          }
          ueMarketplaceAsset[priceArray[key]] = newArray;
        }
        var assetRemoveKeys = JSON.parse('{"id":"' + ueMarketplaceAsset.id + '"}');
        for (var key = 0; key < removeKeys.length; key++) {
          if (ueMarketplaceAsset.hasOwnProperty(removeKeys[key])) {
            assetRemoveKeys[removeKeys[key]] = ueMarketplaceAsset[removeKeys[key]];
            delete ueMarketplaceAsset[removeKeys[key]];
          }
        }
        allAssetsRemovedKeysJSON.assets.push(assetRemoveKeys);
        allAssetsRemovedKeysJSON.total++;
        ueMarketplaceAsset.lastScan = new Date().toISOString();
        allAssetsJSON.assets.push(ueMarketplaceAsset);
        allAssetsJSON.total++;
      }
    }
  }
  //console.log(JSON.stringify(allAssetsRemovedKeysJSON));
  const fs = require('fs');
  fs.writeFile('allAssets.json', JSON.stringify(allAssetsJSON), function (err) {
    if (err) return console.log(err);
    console.log('Saved allAssets.json');
  });
  fs.writeFile('allAssetsRemovedKeys.json', JSON.stringify(allAssetsRemovedKeysJSON), function (err) {
    if (err) return console.log(err);
    console.log('Saved allAssetsRemovedKeys.json');
  });
}

function findAssetByID(assets, id, index) {
  if (index === undefined) {
    index = 0;
  }
  for (var i = index; i < assets.length; i++){
    if (assets[i].id == id){
        console.log(i);
      return i;
    }
  }
  return -1;
}