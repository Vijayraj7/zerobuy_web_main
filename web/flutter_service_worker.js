'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';

const RESOURCES = {"assets/AssetManifest.bin": "5846a300c80ef683fb96313a1669a358",
"assets/AssetManifest.bin.json": "d25d03ae5d04c9fc49448dd7a838312b",
"assets/assets/font/Mulish-bold.ttf": "b7fa867b7522c7629eca3c4b9f31d3c8",
"assets/assets/font/Mulish-Italic.ttf": "1936c9c15bd37eb6019c0e46f8cf867b",
"assets/assets/font/Mulish-light.ttf": "a12ad93579e2da6f8cecb2e89f413a4c",
"assets/assets/font/Mulish-Medium.ttf": "95fb28784ad39295fdd64be6662d81d7",
"assets/assets/font/Mulish-Regular.ttf": "5416a925ffafb775c6bffd116d87deb0",
"assets/assets/json/countries.json": "a1c5eda9a4b393e1b84f96b8b0d805d2",
"assets/assets/png/banner_image.png": "d28e875b12973ec43ca9a7cc9fa3023e",
"assets/assets/png/call.png": "bd1d8a8673955e9af1a255e54dc89775",
"assets/assets/png/card.png": "1701fa7e5690553a68e4f0e8c1926d13",
"assets/assets/png/cash.png": "a585c133be17af818810312cf0352032",
"assets/assets/png/confirm_otp.png": "9dddfeb98d36ea416abd261da7aab463",
"assets/assets/png/empty_cart.png": "ed11b730762a8a4afca5e28abd64ef30",
"assets/assets/png/favicon.png": "9e1a28f4e2abfaca8f6a4d7400414f1f",
"assets/assets/png/favicon1.png": "2cb9cab3ab060afd333c050d3b0350d6",
"assets/assets/png/messenger.png": "58b55ab80084165278d10f114b351b38",
"assets/assets/png/no_internet_connection.png": "2bb7eb3b5cad88c42ff0ff88175feade",
"assets/assets/png/onboarding_one.png": "01fb09908d918cb07bd92332bc113e78",
"assets/assets/png/onboarding_three.png": "ad29adc373682cccb2827b743caa79af",
"assets/assets/png/onboarding_two.png": "467ae1a39c0a4a1c55fa822c3df52b7f",
"assets/assets/png/placeholder_image.png": "8cb68ce29961efecf98e5817deab584b",
"assets/assets/png/result_not_found.png": "07513c17225fe0146fb2189e145258ab",
"assets/assets/png/return_selected.png": "f30d85555b743761d4d8ec789cf44e76",
"assets/assets/png/searce_result.png": "8f1c8e2c21cf6703a233055d2d76d2c0",
"assets/assets/png/splash_logo.png": "850254189b5e23e33af5e44ffc4f1481",
"assets/assets/png/splash_logo1.png": "abf2b071b359f5a350cf865503cc5add",
"assets/assets/png/whatsapp.png": "0bc7d751710e362ebe33260c233a8e78",
"assets/assets/svg/active_bag.svg": "dbee6e5c8350221ec84c06185ec58a73",
"assets/assets/svg/active_categories.svg": "6de6c525636e11fa2d890dcf998c283a",
"assets/assets/svg/active_favorite.svg": "462d7fbf66f94c718e576ba1a0b05606",
"assets/assets/svg/active_home.svg": "cf6b1c1a2db600d36253a83b14f7504f",
"assets/assets/svg/active_more.svg": "7945b0f03bcfe792b33157ee4f4e2f3c",
"assets/assets/svg/active_radio.svg": "eb675822565223f4b5a41c9221d36e88",
"assets/assets/svg/active_shop.svg": "304a9d1f4d3abc64e07873ad8d21c569",
"assets/assets/svg/active_support.svg": "ff13487367fd61396171cb7fc44451b5",
"assets/assets/svg/arrow-left.svg": "07f0774df4afe072d52c7d585a28016a",
"assets/assets/svg/arrow-right.svg": "07f0774df4afe072d52c7d585a28016a",
"assets/assets/svg/bag.svg": "23b206e2bbdf8c22d6ebd989648ef4d1",
"assets/assets/svg/blog.svg": "1f0c737fbcd8a7ce596ca72522b6a04d",
"assets/assets/svg/cancel_icon.svg": "00a5c601537976a68fe23fe4fb4338d5",
"assets/assets/svg/cart.svg": "48e7e61f313ca9f8012ca4885554ea51",
"assets/assets/svg/cash.svg": "d020ffc15ed60997ee283b16b1b710a0",
"assets/assets/svg/clock.svg": "76214dd71dec65768f85c08d6f8d3193",
"assets/assets/svg/confirm_otp.svg": "557701fad20b02af3967d0ef90a4ec7f",
"assets/assets/svg/cuppon.svg": "fcfcf427a0cdb5f5ced5c0fddaef1e83",
"assets/assets/svg/currency.svg": "039fde2f36da31bffb6f4bf090f2042e",
"assets/assets/svg/done_icon.svg": "bac9ecf897345849794bb0ae9022f66f",
"assets/assets/svg/edit.svg": "6ecfe84aa7746b05b5f99cf224b861d2",
"assets/assets/svg/envelope.svg": "66d93a6e88cebea62a0778745b65f96b",
"assets/assets/svg/eye.svg": "df4dd7aa15dac45f9ddf5d16bfdd270f",
"assets/assets/svg/facebook.svg": "1c79e66e44dcc87a54cfff4df321b4cb",
"assets/assets/svg/fill_location.svg": "e9f28374ce1c350cdcce9f665f059f76",
"assets/assets/svg/filter.svg": "392a70c82f494324dcf2978ce52f10bd",
"assets/assets/svg/gift.svg": "12370c51131991c3c4596439bacfbc05",
"assets/assets/svg/grid.svg": "7c0ac0fad7455b399e63f8f74cc8708a",
"assets/assets/svg/heart.svg": "aad10819b7f880ba8d69ee4489013d10",
"assets/assets/svg/image.svg": "ddc8808dd687e447bb1f6f74b2f30165",
"assets/assets/svg/inactive_bag.svg": "904bc1049645cbd0a1b35be72839de4b",
"assets/assets/svg/inactive_categories.svg": "995370c1c7a3c52a81a04de9d0842175",
"assets/assets/svg/inactive_favorite.svg": "86606cc3ea8b91cb4b083791a25ed9c8",
"assets/assets/svg/inactive_home.svg": "b01e2104e2bd7372b1dcb89a721e38a1",
"assets/assets/svg/inactive_more.svg": "fa5b3e3de137642b2a8432a2ffd5db9d",
"assets/assets/svg/inactive_shop.svg": "1618000de1b624181e110a24d44b05fa",
"assets/assets/svg/inactive_support.svg": "b1c48e5f574aa4970cbd94046a211122",
"assets/assets/svg/key.svg": "53a26fa3058942bcdd1cd52f27d95f48",
"assets/assets/svg/linked_in.svg": "83d8ffb60153b8fa053eeebdb08fcc35",
"assets/assets/svg/list.svg": "c0afaa44f244b9344b6491e3dbf33b8f",
"assets/assets/svg/location.svg": "14360a1bbf4cf232a6f4c6ad37d090da",
"assets/assets/svg/location_purple.svg": "be0bb88311b88d6835a0d721a412047f",
"assets/assets/svg/logout.svg": "63b5d69eed0ad54296ecb3ebb2020ad8",
"assets/assets/svg/message.svg": "bb7aed13ef59140fc52c9458a66f09d9",
"assets/assets/svg/money-change.svg": "2fceff0ecf14ff150dfa72dd739f67ec",
"assets/assets/svg/notification.svg": "b29a71a1bc20e8f1f5483f797bc75e6e",
"assets/assets/svg/phone.svg": "5dbcb3340a0618deaf1d33025ce69c4e",
"assets/assets/svg/pinterest.svg": "60067b5e308b7113ddb26a729e8b0174",
"assets/assets/svg/privacy.svg": "86e5ccd3426d32607a176791fdc01e6a",
"assets/assets/svg/profile.svg": "c01d05705d01f42454a226df619fa2b3",
"assets/assets/svg/radio.svg": "e1b6fa4bbbb18d564152de6f752168a1",
"assets/assets/svg/radio_icon.svg": "977787d6d5b5f48ef632d5998abc9dd0",
"assets/assets/svg/receipt.svg": "f400e7fab397cc19053eeec6d64d8aea",
"assets/assets/svg/recoverp_password.svg": "3a8356aaca2f895e87f1535657f5f597",
"assets/assets/svg/refund.svg": "f356e0595f500636cd177e9909c524fe",
"assets/assets/svg/search.svg": "479eae10b8de53096a6f26420c1e1b5e",
"assets/assets/svg/search_home.svg": "f4d7ed358e4f73ce954bf7767daa3070",
"assets/assets/svg/send-right.svg": "8268f2b12d504b31870b5df9366e8b67",
"assets/assets/svg/share.svg": "94e79c3ba6fc60bde3a85118b05ef7e9",
"assets/assets/svg/shield-check.svg": "c291a7ea2aedb66895f7fb4dd4d04f5f",
"assets/assets/svg/shield-times.svg": "d56e5baaac1f14c28fac3e35a5467b2d",
"assets/assets/svg/support.svg": "d611967f65212b382f3e6a847288a970",
"assets/assets/svg/terms.svg": "97915168b2db004e3f409087661ce599",
"assets/assets/svg/ticket.svg": "da326ff67c5f08009e5a9e5e3e0cc652",
"assets/assets/svg/translate.svg": "0215e6cc94947a0aabe7ec9221c32b35",
"assets/assets/svg/trash.svg": "c4a856887b12a56f81b67f16ef334714",
"assets/assets/svg/twitter.svg": "cc2ed093b3428a33fe41315c98064599",
"assets/FontManifest.json": "d0b30c7ab69b90e340fd372df28c6627",
"assets/fonts/MaterialIcons-Regular.otf": "f4fb667b3d660bf3d50b10c081e1ac7e",
"assets/NOTICES": "7054923687ff7a757ad54831c505eb25",
"assets/packages/cupertino_icons/assets/CupertinoIcons.ttf": "d7d83bd9ee909f8a9b348f56ca7b68c6",
"assets/packages/fluttertoast/assets/toastify.css": "a85675050054f179444bc5ad70ffc635",
"assets/packages/fluttertoast/assets/toastify.js": "56e2c9cedd97f10e7e5f1cebd85d53e3",
"assets/packages/flutter_inappwebview/assets/t_rex_runner/t-rex.css": "5a8d0222407e388155d7d1395a75d5b9",
"assets/packages/flutter_inappwebview/assets/t_rex_runner/t-rex.html": "16911fcc170c8af1c5457940bd0bf055",
"assets/packages/flutter_inappwebview_web/assets/web/web_support.js": "509ae636cfdd93e49b5a6eaf0f06d79f",
"assets/packages/wakelock_plus/assets/no_sleep.js": "7748a45cd593f33280669b29c2c8919a",
"assets/packages/youtube_player_flutter/assets/speedometer.webp": "50448630e948b5b3998ae5a5d112622b",
"assets/shaders/ink_sparkle.frag": "ecc85a2e95f5e9f53123dcaf8cb9b6ce",
"assets/shaders/stretch_effect.frag": "40d68efbbf360632f614c731219e95f0",
"canvaskit/canvaskit.js": "8331fe38e66b3a898c4f37648aaf7ee2",
"canvaskit/canvaskit.js.symbols": "a3c9f77715b642d0437d9c275caba91e",
"canvaskit/canvaskit.wasm": "9b6a7830bf26959b200594729d73538e",
"canvaskit/chromium/canvaskit.js": "a80c765aaa8af8645c9fb1aae53f9abf",
"canvaskit/chromium/canvaskit.js.symbols": "e2d09f0e434bc118bf67dae526737d07",
"canvaskit/chromium/canvaskit.wasm": "a726e3f75a84fcdf495a15817c63a35d",
"canvaskit/skwasm.js": "8060d46e9a4901ca9991edd3a26be4f0",
"canvaskit/skwasm.js.symbols": "3a4aadf4e8141f284bd524976b1d6bdc",
"canvaskit/skwasm.wasm": "7e5f3afdd3b0747a1fd4517cea239898",
"canvaskit/skwasm_heavy.js": "740d43a6b8240ef9e23eed8c48840da4",
"canvaskit/skwasm_heavy.js.symbols": "0755b4fb399918388d71b59ad390b055",
"canvaskit/skwasm_heavy.wasm": "b0be7910760d205ea4e011458df6ee01",
"favicon.png": "5dcef449791fa27946b3d35ad8803796",
"firebase-messaging-sw.js": "64fa92d5f8be4a88d5909051419e856b",
"flutter.js": "24bc71911b75b5f8135c949e27a2984e",
"flutter_bootstrap.js": "4b22b6fe18112da36e3664cd04b3c15e",
"icons/Icon-192.png": "ac9a721a12bbc803b44f645561ecb1e1",
"icons/Icon-512.png": "96e752610906ba2a93c65f8abe1645f1",
"icons/Icon-maskable-192.png": "c457ef57daa1d16f64b27b786ec2ea3c",
"icons/Icon-maskable-512.png": "301a7604d45b3e739efc881eb04896ea",
"index.html": "d1f75b80205bce7fcb81374fc3b93fcd",
"/": "d1f75b80205bce7fcb81374fc3b93fcd",
"main.dart.js": "2b41bd408cc943b84df0138bab32599f",
"manifest.json": "6790e9a4112008c4c8aada144c9379ec",
"version.json": "d616aca920363bed30b3a5c91bc46c9b"};
// The application shell files that are downloaded before a service worker can
// start.
const CORE = ["main.dart.js",
"index.html",
"flutter_bootstrap.js",
"assets/AssetManifest.bin.json",
"assets/FontManifest.json"];

// During install, the TEMP cache is populated with the application shell files.
self.addEventListener("install", (event) => {
  self.skipWaiting();
  return event.waitUntil(
    caches.open(TEMP).then((cache) => {
      return cache.addAll(
        CORE.map((value) => new Request(value, {'cache': 'reload'})));
    })
  );
});
// During activate, the cache is populated with the temp files downloaded in
// install. If this service worker is upgrading from one with a saved
// MANIFEST, then use this to retain unchanged resource files.
self.addEventListener("activate", function(event) {
  return event.waitUntil(async function() {
    try {
      var contentCache = await caches.open(CACHE_NAME);
      var tempCache = await caches.open(TEMP);
      var manifestCache = await caches.open(MANIFEST);
      var manifest = await manifestCache.match('manifest');
      // When there is no prior manifest, clear the entire cache.
      if (!manifest) {
        await caches.delete(CACHE_NAME);
        contentCache = await caches.open(CACHE_NAME);
        for (var request of await tempCache.keys()) {
          var response = await tempCache.match(request);
          await contentCache.put(request, response);
        }
        await caches.delete(TEMP);
        // Save the manifest to make future upgrades efficient.
        await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
        // Claim client to enable caching on first launch
        self.clients.claim();
        return;
      }
      var oldManifest = await manifest.json();
      var origin = self.location.origin;
      for (var request of await contentCache.keys()) {
        var key = request.url.substring(origin.length + 1);
        if (key == "") {
          key = "/";
        }
        // If a resource from the old manifest is not in the new cache, or if
        // the MD5 sum has changed, delete it. Otherwise the resource is left
        // in the cache and can be reused by the new service worker.
        if (!RESOURCES[key] || RESOURCES[key] != oldManifest[key]) {
          await contentCache.delete(request);
        }
      }
      // Populate the cache with the app shell TEMP files, potentially overwriting
      // cache files preserved above.
      for (var request of await tempCache.keys()) {
        var response = await tempCache.match(request);
        await contentCache.put(request, response);
      }
      await caches.delete(TEMP);
      // Save the manifest to make future upgrades efficient.
      await manifestCache.put('manifest', new Response(JSON.stringify(RESOURCES)));
      // Claim client to enable caching on first launch
      self.clients.claim();
      return;
    } catch (err) {
      // On an unhandled exception the state of the cache cannot be guaranteed.
      console.error('Failed to upgrade service worker: ' + err);
      await caches.delete(CACHE_NAME);
      await caches.delete(TEMP);
      await caches.delete(MANIFEST);
    }
  }());
});
// The fetch handler redirects requests for RESOURCE files to the service
// worker cache.
self.addEventListener("fetch", (event) => {
  if (event.request.method !== 'GET') {
    return;
  }
  var origin = self.location.origin;
  var key = event.request.url.substring(origin.length + 1);
  // Redirect URLs to the index.html
  if (key.indexOf('?v=') != -1) {
    key = key.split('?v=')[0];
  }
  if (event.request.url == origin || event.request.url.startsWith(origin + '/#') || key == '') {
    key = '/';
  }
  // If the URL is not the RESOURCE list then return to signal that the
  // browser should take over.
  if (!RESOURCES[key]) {
    return;
  }
  // If the URL is the index.html, perform an online-first request.
  if (key == '/') {
    return onlineFirst(event);
  }
  event.respondWith(caches.open(CACHE_NAME)
    .then((cache) =>  {
      return cache.match(event.request).then((response) => {
        // Either respond with the cached resource, or perform a fetch and
        // lazily populate the cache only if the resource was successfully fetched.
        return response || fetch(event.request).then((response) => {
          if (response && Boolean(response.ok)) {
            cache.put(event.request, response.clone());
          }
          return response;
        });
      })
    })
  );
});
self.addEventListener('message', (event) => {
  // SkipWaiting can be used to immediately activate a waiting service worker.
  // This will also require a page refresh triggered by the main worker.
  if (event.data === 'skipWaiting') {
    self.skipWaiting();
    return;
  }
  if (event.data === 'downloadOffline') {
    downloadOffline();
    return;
  }
});
// Download offline will check the RESOURCES for all files not in the cache
// and populate them.
async function downloadOffline() {
  var resources = [];
  var contentCache = await caches.open(CACHE_NAME);
  var currentContent = {};
  for (var request of await contentCache.keys()) {
    var key = request.url.substring(origin.length + 1);
    if (key == "") {
      key = "/";
    }
    currentContent[key] = true;
  }
  for (var resourceKey of Object.keys(RESOURCES)) {
    if (!currentContent[resourceKey]) {
      resources.push(resourceKey);
    }
  }
  return contentCache.addAll(resources);
}
// Attempt to download the resource online before falling back to
// the offline cache.
function onlineFirst(event) {
  return event.respondWith(
    fetch(event.request).then((response) => {
      return caches.open(CACHE_NAME).then((cache) => {
        cache.put(event.request, response.clone());
        return response;
      });
    }).catch((error) => {
      return caches.open(CACHE_NAME).then((cache) => {
        return cache.match(event.request).then((response) => {
          if (response != null) {
            return response;
          }
          throw error;
        });
      });
    })
  );
}
