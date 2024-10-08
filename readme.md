### That's modified version of https://github.com/gg-innovative/larafirebase . For personal use only. I don't claim any rights


### Introduction

**Larafirebase** is a package thats offers you to send push notifications via Firebase in Laravel.

Firebase Cloud Messaging (FCM) is a cross-platform messaging solution that lets you reliably deliver messages at no cost.

For use cases such as instant messaging, a message can transfer a payload of up to 4KB to a client app.

### Installation

Follow the steps below to install the package.


**Install via Composer**

```
composer require aristosya/larafirebase:dev-main

```

**Copy Configuration**

Run the following command to publish the `larafirebase.php` config file:

```bash
php artisan vendor:publish --provider="GGInnovative\Larafirebase\Providers\LarafirebaseServiceProvider"
```

**Configure larafirebase.php as needed**

Open the `larafirebase.php` configuration file, which you just published, and set the following values as needed:

- `project_id`: Replace with your actual Firebase project ID. (To get your project ID go to https://console.firebase.google.com/ -> choose your project -> Find "project settings (Inside Side-bar click on gear for now)" -> In "General" tab copy your "Project ID"). BTW it must be a string.
- `firebase_credentials`: This refers to the JSON credentials file for your Firebase project. Make sure it points to the correct location in your project. This JSON file contains the authentication information for your Firebase project, allowing your Laravel application to interact with Firebase services. You can generate this JSON file in the Firebase Console. Once you have it, specify its path in this configuration. (To get your project JSON credentials FILE go to https://console.firebase.google.com/ -> choose your project -> Find "project settings (Inside Side-bar click on gear for now)" -> In "Service accounts" tab choose Firebase Admin SDK -> Generate new private key -> download the file and then put it inside your app"). BTW for 'firebase_credentials' => public_path('firebase_credentials.json') the file must be inside {project-folder}/public/, and the name of the file must be : "firebase_credentials.json".


### Configure your front application for sendNotificationAll() method

IF you will use send notifications to all user, you should initialize all the devise tokens to topic "all" inside your front application (NOT FOR LARAVEL). 
Example for Flutter:
```flutter
import 'dart:convert';

import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:get/get.dart';

import '../routes/app_routes.dart';


void onDidReceiveNotificationResponse(
    NotificationResponse notificationResponse) async {
  final payload =
      RemoteMessage.fromMap(jsonDecode(notificationResponse.payload ?? ""));
  if (notificationResponse.payload != null) {
    debugPrint('notification payload: $payload');
  }
  await Get.toNamed(Routes.testNotification, arguments: {"message": payload});
}

class FirebaseApi {
  final _firebaseMessaging = FirebaseMessaging.instance;

  final _androidChannel = const AndroidNotificationChannel(
      'high_importance_channel', 'High importance notifications',
      description: 'This channel is used for ipmortant notications',
      importance: Importance.defaultImportance);

  final _localNotifications = FlutterLocalNotificationsPlugin();

  Future<void> initNotifications() async {
    await _firebaseMessaging.requestPermission();
    final fcmToken = await _firebaseMessaging.getToken();

// NEXT LINE MUST BE ADDED 
    _firebaseMessaging.subscribeToTopic('all');  
    debugPrint('FireBase Cloud Messaging Token == $fcmToken');
    initPushNotifications();
    initLocalNotifications();
  }

  // function to handle received message
  void handleMessage(RemoteMessage? message) async {
    if (message == null) {
      return;
    }
    await Get.toNamed(Routes.testNotification, arguments: {"message": message});
  }

  Future initLocalNotifications() async {
// initialise the plugin. app_icon needs to be a added as a drawable resource to the Android head project
    const AndroidInitializationSettings initializationSettingsAndroid =
        AndroidInitializationSettings('@mipmap/ic_launcher');
    final InitializationSettings initializationSettings =
        InitializationSettings(
      android: initializationSettingsAndroid,
    );
    await _localNotifications.initialize(initializationSettings,
        onDidReceiveNotificationResponse: onDidReceiveNotificationResponse);
    final _platform = _localNotifications.resolvePlatformSpecificImplementation<
        AndroidFlutterLocalNotificationsPlugin>();
    await _platform?.createNotificationChannel(_androidChannel);
  }

  //function to init bg settings
  Future initPushNotifications() async {
    // handle when the app was terminated and it opeened now
    await FirebaseMessaging.instance
        .setForegroundNotificationPresentationOptions(
            alert: true, badge: true, sound: true);
    await FirebaseMessaging.instance.getInitialMessage().then(handleMessage);
    // attach an event listener
    FirebaseMessaging.onMessageOpenedApp.listen(handleMessage);
    FirebaseMessaging.onMessage.listen((message) {
      final notification = message.notification;
      if (notification == null) return;
      _localNotifications.show(
          notification.hashCode,
          notification.title,
          notification.body,
          NotificationDetails(
              android: AndroidNotificationDetails(
                  _androidChannel.id, _androidChannel.name,
                  channelDescription: _androidChannel.description,
                  icon: '@mipmap/ic_launcher')),
          payload: jsonEncode(message.toMap()));
    });
  }
}
```
If Im not mistaken for JAVA :
```java
    FirebaseMessaging.getInstance().subscribeToTopic("TopicName");
```


### Configure your front application for sendNotificationUser()/sendNotificationUsers() method

IF you will use send notifications to specific user/users by his/their id, you should initialize all the devise tokens to topic "user_ID" inside your front application after login or registration, also delete deviceId from that topic after logout ! (NOT FOR LARAVEL). 

Example for Flutter:
```flutter
void login(){
    final _firebaseMessaging = FirebaseMessaging.instance;
    _firebaseMessaging.subscribeToTopic('user_$id');
}
void logout(){
    final _firebaseMessaging = FirebaseMessaging.instance;
    _firebaseMessaging.unsubscribeFromTopic('user_$id');
}
```

```java
    login => FirebaseMessaging.getInstance().subscribeToTopic("user_" + id);
    logout=> FirebaseMessaging.getInstance().unsubscribeFromTopic("user_" + id);
```



### Usage
Follow the steps below to find how to use the package.

Example usage in **Controller/Service** or any class:

```php
use GGInnovative\Larafirebase\Facades\Larafirebase;

class MyController
{
public function sendNotification()
    {
        return Larafirebase::withTitle('Test Title')
            ->withBody('Test body')
            ->withImage('https://firebase.google.com/images/social.png')
            ->withAdditionalData([
                'name' => 'wrench',
                'mass' => '1.3kg',
                'count' => '3'
            ])
            ->withToken('TOKEN_HERE') // You can use also withTopic
            ->sendNotification();
    }


public function sendNotificationAll()
            {
                return Larafirebase::withTitle('Test Title')
                    ->withBody('Test body')
                    ->withImage('https://firebase.google.com/images/social.png')
                    ->withAdditionalData([
                        'name' => 'wrench',
                        'mass' => '1.3kg',
                        'count' => '3'
                    ])
                    ->sendNotificationAll();
                
        }



public function sendNotificationUser()
    {
        return Larafirebase::withTitle('Test Title')
            ->withBody('Test body')
            ->withImage('https://firebase.google.com/images/social.png')
            ->withAdditionalData([
                'name' => 'Some Name',
                'product_id' => '123',
                'user_id' => '3'
            ])
//            id of One user
            ->sendNotificationUser(1);
    }


public function sendNotificationUsers()
    {
        return Larafirebase::withTitle('Test Title')
            ->withBody('Test body')
            ->withImage('https://firebase.google.com/images/social.png')
            ->withAdditionalData([
                'name' => 'Some Name',
                'product_id' => '123',
                'user_id' => '3'
            ])
//            array of users ids
            ->sendNotificationUsers([0,2,1,4]);
    }
```


