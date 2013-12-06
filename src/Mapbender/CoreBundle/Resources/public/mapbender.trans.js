var Mapbender = Mapbender || {};
Mapbender.i18n = {};
Mapbender.trans = function(key, placeholders) {
    if(!Mapbender.i18n[key]){
        return key;
    } else {
        //@TODO replace placeholders with values
        return Mapbender.i18n[key];
    }
};
