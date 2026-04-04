'use strict';
const MANIFEST = 'flutter-app-manifest';
const TEMP = 'flutter-temp-cache';
const CACHE_NAME = 'flutter-app-cache';

const RESOURCES = {"assets/AssetManifest.bin": "e949bd02daea79ce46e542428db0fb69",
"assets/AssetManifest.bin.json": "461e003f069f28d69ace01006a83375a",
"assets/assets/font/Mulish-bold.ttf": "b7fa867b7522c7629eca3c4b9f31d3c8",
"assets/assets/font/Mulish-Italic.ttf": "1936c9c15bd37eb6019c0e46f8cf867b",
"assets/assets/font/Mulish-light.ttf": "a12ad93579e2da6f8cecb2e89f413a4c",
"assets/assets/font/Mulish-Medium.ttf": "95fb28784ad39295fdd64be6662d81d7",
"assets/assets/font/Mulish-Regular.ttf": "5416a925ffafb775c6bffd116d87deb0",
"assets/assets/json/countries.json": "dfe1ea6ec98d8d2aea7cacdb5ebcd6a1",
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
"assets/assets/png/shop_bg.png": "f495b0c82c2ce25a078fddf524fc12ec",
"assets/assets/png/splash_logo.png": "850254189b5e23e33af5e44ffc4f1481",
"assets/assets/png/splash_logo1.png": "abf2b071b359f5a350cf865503cc5add",
"assets/assets/png/whatsapp.png": "0bc7d751710e362ebe33260c233a8e78",
"assets/assets/svg/active_bag.svg": "7e2cce0b1fc7eea1503b120270c8b60e",
"assets/assets/svg/active_categories.svg": "ac1cf645e901faa63d30ac2d9689ed83",
"assets/assets/svg/active_favorite.svg": "462d7fbf66f94c718e576ba1a0b05606",
"assets/assets/svg/active_home.svg": "ecc6b6ada5038f370da37a778e708c1d",
"assets/assets/svg/active_more.svg": "d32129e538156f09c9fe8692b9fe3ef6",
"assets/assets/svg/active_radio.svg": "363e9124d7ff41b167e4d47b46fdd523",
"assets/assets/svg/active_shop.svg": "44b7569a9ac335ef42855ca99dbd3c32",
"assets/assets/svg/active_support.svg": "7268dda4db112998676fc8ee2016a7ea",
"assets/assets/svg/arrow-left.svg": "cc808437df3a8f84c8215068c2b3ec74",
"assets/assets/svg/arrow-right.svg": "cc808437df3a8f84c8215068c2b3ec74",
"assets/assets/svg/bag.svg": "9ca8f43b73523c04567399e71a529227",
"assets/assets/svg/blog.svg": "e2b5a6103ae0b290a15b34367503c843",
"assets/assets/svg/cancel_icon.svg": "4d003efb4fe30f3ad33ce1e4691e259c",
"assets/assets/svg/cart.svg": "bae7339063350ec2f76a08ca6843af28",
"assets/assets/svg/cash.svg": "1e88de3bf98362c91fcfb60cfbf92e58",
"assets/assets/svg/clock.svg": "70dcbfcd2ab0c4d21cc19d5cb8a1cfe1",
"assets/assets/svg/confirm_otp.svg": "ed4ee9e84d9fefad75e48d88faac6041",
"assets/assets/svg/cuppon.svg": "fdd7d218ba93ce1aa674c0eab4afc5d2",
"assets/assets/svg/currency.svg": "259bb93512d421be6289d187c1850fa8",
"assets/assets/svg/done_icon.svg": "b6d895e8dae13b900d54922a965961ca",
"assets/assets/svg/edit.svg": "677575c6a29631bed41ac7c2c2cd7ecc",
"assets/assets/svg/envelope.svg": "fc6dc4febb162f3c831f7f4bf521bf2d",
"assets/assets/svg/eye.svg": "313c1dfaab6d2cb03073f90c64086ce1",
"assets/assets/svg/facebook.svg": "2b79e6063a397554f929f87f04ec6f8b",
"assets/assets/svg/fill_location.svg": "acad7bd7f1b13b79b92164536d09ebf0",
"assets/assets/svg/filter.svg": "9ebe1070da4648c91100a39c2451de2c",
"assets/assets/svg/gift.svg": "70ce49dca6da0ce6d54e506e6ce1dd2e",
"assets/assets/svg/grid.svg": "2c623d16a5afec63c191fb6d4f07e0c3",
"assets/assets/svg/heart.svg": "1beccbc2f385b62b2bdc2ea9c4f93c0b",
"assets/assets/svg/image.svg": "d4a331703601cb736036be1af4a4d33a",
"assets/assets/svg/inactive_bag.svg": "287af93d1bba83427278cd752cc7a329",
"assets/assets/svg/inactive_categories.svg": "d289601505e6a299fa12ac8caff61fa8",
"assets/assets/svg/inactive_favorite.svg": "86606cc3ea8b91cb4b083791a25ed9c8",
"assets/assets/svg/inactive_home.svg": "f9862060faf0c293608003f9eb087381",
"assets/assets/svg/inactive_more.svg": "ae57293c324db27e051cc33726a59664",
"assets/assets/svg/inactive_shop.svg": "7bff65cac9a08155e20fb745fdcbfb40",
"assets/assets/svg/inactive_support.svg": "b591534f6fc5de5c0af7beaf2116b02a",
"assets/assets/svg/key.svg": "719222818fb85f778511b5bcae8c4527",
"assets/assets/svg/linked_in.svg": "6cdc26fe5adf6b299366b42400b9157b",
"assets/assets/svg/list.svg": "def3ec8caf0acea3052cddf1e040b7cc",
"assets/assets/svg/location.svg": "72b6fe95c358679bb03f795701eb0960",
"assets/assets/svg/location_purple.svg": "869f17ec53a6dcb6120469fb58bbadf5",
"assets/assets/svg/logout.svg": "453b4c801b46f8cd6e6bcee1c02332b3",
"assets/assets/svg/message.svg": "4942a2e28b2e5ff324510722bd3b6811",
"assets/assets/svg/money-change.svg": "a94b271d188fcbf00078a804dfbb3f7d",
"assets/assets/svg/notification.svg": "a51978367a32aa971ee9813df5341e3e",
"assets/assets/svg/phone.svg": "24ccca0381dad6410342a10f9e29fc06",
"assets/assets/svg/pinterest.svg": "223244e73e7be86cb49b0e826c78239b",
"assets/assets/svg/privacy.svg": "c71b8cf01674d385a07810c04d456693",
"assets/assets/svg/profile.svg": "176f3ab2a4ef51ced295ae5f238017f0",
"assets/assets/svg/radio.svg": "17a2d277e26f46bc787d0788ce52cdc1",
"assets/assets/svg/radio_icon.svg": "a994849174bb066436ff80c777a5df2d",
"assets/assets/svg/receipt.svg": "64844ece371cfdc2697bbfc309e9d56d",
"assets/assets/svg/recoverp_password.svg": "80e8e5af21cd8cd1609754d4a3e379b6",
"assets/assets/svg/refund.svg": "9262c2138375311eaeb245bc1bc7a875",
"assets/assets/svg/search.svg": "cf431d01d960c033c917deda13d9e44b",
"assets/assets/svg/search_home.svg": "475347d75d0497efa1844180b0bba2d7",
"assets/assets/svg/send-right.svg": "3607e07daec685e9630b471216e4fb8d",
"assets/assets/svg/share.svg": "be4adfd1f501635181cb7b38be5c5bdd",
"assets/assets/svg/shield-check.svg": "8c62c4096ea1c93f6c75ef636ee33f95",
"assets/assets/svg/shield-times.svg": "a731b4443f33a0b490583c99fd2f7241",
"assets/assets/svg/shop-bg.svg": "b645c27da7815ca3d509ada0e977c1a1",
"assets/assets/svg/support.svg": "bd1710f8ae67ad80e4e1db915d2da3eb",
"assets/assets/svg/terms.svg": "8a6ce6f878d82d5d36250149179a32fb",
"assets/assets/svg/ticket.svg": "61d8ab8d04e77c918ab4addaa606c879",
"assets/assets/svg/translate.svg": "151465db4f79ff6c9c29383b12310d9e",
"assets/assets/svg/trash.svg": "c15b64390d98d039cb582381f1df2c65",
"assets/assets/svg/twitter.svg": "c6153e673243950dee59da508428da5a",
"assets/FontManifest.json": "5dd54210ea97638c24dbec575e663b7e",
"assets/fonts/MaterialIcons-Regular.otf": "1f9f615b01d30d2c3ee5a3f75417b18e",
"assets/NOTICES": "4dd21a06a1e0442c91c99173631ec264",
"assets/packages/cupertino_icons/assets/CupertinoIcons.ttf": "d7d83bd9ee909f8a9b348f56ca7b68c6",
"assets/packages/fluttertoast/assets/toastify.css": "a85675050054f179444bc5ad70ffc635",
"assets/packages/fluttertoast/assets/toastify.js": "56e2c9cedd97f10e7e5f1cebd85d53e3",
"assets/packages/flutter_cashfree_pg_sdk/assets/amex.png": "99f1d408e289af3e6359feffc5abc003",
"assets/packages/flutter_cashfree_pg_sdk/assets/credit-card-default.png": "e987949373676bb7b9a6bb85c19dba1b",
"assets/packages/flutter_cashfree_pg_sdk/assets/diners.png": "6bc0a26fbe98312d2cde3ca06a9b9518",
"assets/packages/flutter_cashfree_pg_sdk/assets/discover.png": "8fb5c3dd58ffb198644a9aac0d0a5da2",
"assets/packages/flutter_cashfree_pg_sdk/assets/jcb.png": "903e2793c61fc15e48fed184d6eadbe7",
"assets/packages/flutter_cashfree_pg_sdk/assets/maestro.png": "49f3167896883d38eb9770f6527fa961",
"assets/packages/flutter_cashfree_pg_sdk/assets/mastercard.png": "64dd58b0f24ee7bf272d964f508660bb",
"assets/packages/flutter_cashfree_pg_sdk/assets/rupay.png": "b6c88a3273204df54bc46e374b633570",
"assets/packages/flutter_cashfree_pg_sdk/assets/visa.png": "3442819455f79b208c50094bae6843e8",
"assets/packages/flutter_inappwebview/assets/t_rex_runner/t-rex.css": "5a8d0222407e388155d7d1395a75d5b9",
"assets/packages/flutter_inappwebview/assets/t_rex_runner/t-rex.html": "16911fcc170c8af1c5457940bd0bf055",
"assets/packages/flutter_inappwebview_web/assets/web/web_support.js": "509ae636cfdd93e49b5a6eaf0f06d79f",
"assets/packages/font_awesome_flutter/lib/fonts/Font-Awesome-7-Brands-Regular-400.otf": "9bcf52eb78aa09b73a7c81fad5e6698a",
"assets/packages/font_awesome_flutter/lib/fonts/Font-Awesome-7-Free-Regular-400.otf": "b2703f18eee8303425a5342dba6958db",
"assets/packages/font_awesome_flutter/lib/fonts/Font-Awesome-7-Free-Solid-900.otf": "5b8d20acec3e57711717f61417c1be44",
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
"favicon.png": "5d49c6436ecab062e91811ed95d43864",
"firebase-messaging-sw.js": "d9d06cafb6552db133523db3bddff69e",
"flutter.js": "24bc71911b75b5f8135c949e27a2984e",
"flutter_bootstrap.js": "fcfaaec9b8c313fdd1b459e1ddf670f8",
"icons/Icon-192.png": "0a3d604e5b16c63e4e07c5b0373d3505",
"icons/Icon-512.png": "5d49c6436ecab062e91811ed95d43864",
"icons/Icon-maskable-192.png": "7ff1c1fb728acf8cb82bdaef9a00c3db",
"icons/Icon-maskable-512.png": "7abf81196a9636f0f8a19d575696ee44",
"index.html": "7d82d419a36ec786bb269dc1a4a530ac",
"/": "7d82d419a36ec786bb269dc1a4a530ac",
"main.dart.js": "2bc04b57d601d1050df3b3c79d8902a4",
"manifest.json": "ee2108cc86fc1a1b85739a887d8f00b4",
"version.json": "cfee0ffa121d919670f4ea0a4a3345ad"};
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
