# ueMarketplace
Unreal Engine Marketplace API

Node.js is required as well as "bent" module. (npm install bent)
Add all files to the same folder.
Run ueMarketplace.js with Node.js to scan the unreal engine marketplace and save the results to 2 separate json files.

allAssets.json will contain all the asset data except for image references, technicalDetails and longDescription.
The removed data will be stored in allAssetsRemovedKeys.json as well as the asset's id for reference.
