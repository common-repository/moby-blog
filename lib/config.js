jQuery(function() {
    resBannersConfig = resBannersConfig || {};
    new SmartBanner({
        daysHidden: resBannersConfig.daysHidden,
        daysReminder: resBannersConfig.daysReminder,
        appStoreLanguage: resBannersConfig.appStoreLanguage,
        title: resBannersConfig.title,
        author: resBannersConfig.author,
        button: resBannersConfig.button,
        store: {
            ios: resBannersConfig.inAppStore,
            android: resBannersConfig.inGooglePlay,
            /*windows: 'In Windows store'*/
        },
        price: {
            ios: resBannersConfig.price,
            android: resBannersConfig.price,
            //windows: resBannersConfig.price
        }
       // ,force: 'ios' // Uncomment for platform emulation
    });
});