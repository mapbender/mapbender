var Mapbender = Mapbender || {};
Mapbender.i18n = Mapbender.i18n || {};
Mapbender.trans = function(key, replacements) {
    if(!Mapbender.i18n[key]){
        return key;
    } else {
        if(!replacements){
            return Mapbender.i18n[key];
        } else {
            var result = Mapbender.i18n[key];
            for(key in replacements){
                result = result.replace('%'+key+'%',replacements[key]);
            }
            return result;
        }
    }
};
