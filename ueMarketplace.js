const fs = require('fs');
const bent = require('bent');
const getJSON = bent('json');

async function ueMarketplace() {
  var priceArray = ["priceValue","discountPriceValue","discountPercentage","price","discount","discountPrice"];
  //var removeKeys = ["keyImages","featured","thumbnail","learnThumbnail","headerImage","technicalDetails","longDescription"];
  var allAssetsJSON = JSON.parse(fs.readFileSync('allAssets.json'));
  //var allAssetsRemovedKeysJSON = JSON.parse(fs.readFileSync('allAssetsRemovedKeys.json'));
  totalAssetsJSON = await getJSON('https://www.unrealengine.com/marketplace/api/assets?start=0&count=1');
  var totalAssets = totalAssetsJSON.data.paging.total;
  delete totalAssetsJSON;
  console.log('Total assets to scan: '+totalAssets);
  
  for (var start = 0; start < totalAssets; start += 100) {
    console.log('Scanning assets: '+start+' through '+(start+99));
	var ueMarketplaceJSON = await getJSON('https://www.unrealengine.com/marketplace/api/assets?start='+start+'&count=100&sortDir=ASC');
    for (i = 0; i < 100; i++) {
      if (start + i >= totalAssets) {
        break;
      }
      var ueMarketplaceAsset = ueMarketplaceJSON.data.elements[i];
      var foundAsset = findAssetByID(allAssetsJSON.assets, ueMarketplaceAsset.id, Math.max((start+i)-100,0));
      if (foundAsset != -1) {
        for (key = 0; key < priceArray.length; key++) {
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
        for (key = 0; key < priceArray.length; key++) {
          newArray = [];
          if (ueMarketplaceAsset.hasOwnProperty(priceArray[key])) {
            if (ueMarketplaceAsset[priceArray[key]] !== null) {
              newArray.unshift(JSON.parse('{"price":"'+ueMarketplaceAsset[priceArray[key]]+'","date":"'+new Date().toISOString()+'"}'));
            }
          }
          ueMarketplaceAsset[priceArray[key]] = newArray;
        }
        /*var assetRemoveKeys = JSON.parse('{"id":"'+ueMarketplaceAsset.id+'"}');
        for (key = 0; key < removeKeys.length; key++) {
          if (ueMarketplaceAsset.hasOwnProperty(removeKeys[key])) {
            assetRemoveKeys[removeKeys[key]] = ueMarketplaceAsset[removeKeys[key]];
            delete ueMarketplaceAsset[removeKeys[key]];
          }
        }
        allAssetsRemovedKeysJSON.assets.push(assetRemoveKeys);
        allAssetsRemovedKeysJSON.total++;*/
        ueMarketplaceAsset.lastScan = new Date().toISOString();
        allAssetsJSON.assets.push(ueMarketplaceAsset);
        allAssetsJSON.total++;
      }
    }
	console.log(`${Math.round((process.memoryUsage().heapUsed/1024/1024)*100)/100} MB`);
  }
  console.log('Total assets: '+allAssetsJSON.total);
  fs.writeFileSync('allAssets.json', JSON.stringify(allAssetsJSON, null, 2));
  console.log(`${Math.round((process.memoryUsage().heapUsed/1024/1024)*100)/100} MB`);
  //fs.writeFileSync('allAssetsRemovedKeys.json', JSON.stringify(allAssetsRemovedKeysJSON, null, 2));
}

function findAssetByID(assets, id, index) {
  if (index === undefined) {
    index = 0;
  }
  first = 0;
  for (i = index; i <= assets.length; i++) {
	if (first == 0) {
	  first = 1;
	} else if (i == index) {
	  break;
	} else if (i == assets.length) {
	  i = 0;
	}
    if (assets[i].id == id) {
      return i;
    }
  }
  return -1;
}

ueMarketplace()