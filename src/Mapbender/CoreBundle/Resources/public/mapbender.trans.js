var Mapbender = Mapbender || {};
Mapbender.alltranslations = {};
Mapbender.transCallBack = function(objToCallBack, termsToTranslate, saveAtAll) {
    if(typeof(saveAtAll) === 'undefined'){
        saveAtAll = false;
    }
    if(typeof(Mapbender.alltranslations) !== 'undefined'){
        var result = {};
        var ok = true;
        for(var key in termsToTranslate){
            if(typeof(Mapbender.alltranslations[key]) === 'undefined') {
                ok = false;
                break;
            } else {
                result[key] = Mapbender.alltranslations[key];
            }
        }
        if(ok) {
            objToCallBack.transCallBack(result);
            return;
        }
    }
    var mb = this;
    $.ajax({
        url: mb.configuration.transPath,
        type: "post",
        data: termsToTranslate,
        dataType: "json",
        complete: function(jqXHR, textStatus) {
            if(textStatus == "success") {
                termsToTranslate = $.parseJSON(jqXHR.responseText);
                if(saveAtAll) {
                    for(var key in termsToTranslate){
                        Mapbender.alltranslations[key] = termsToTranslate[key];
                    }
                }
            }
            objToCallBack.transCallBack(termsToTranslate);
        }
    });
};

Mapbender.strToHex = function(term) {
    var r="";
    var c=0;
    var h;
    while(c<term.length){
        h=term.charCodeAt(c++).toString(16);
        while(h.length<3) h="0"+h;
        r+=h;
    }
    return r;
//    return term.toLowerCase().replace(/[^a-z0-9]/g, "_");
};

Mapbender.termsToTranslate = function(terms) {
    var translatedterms = {};
    for(var i = 0; i < terms.length; i++) {
        translatedterms[this.strToHex(terms[i])] = terms[i];
    }
    return translatedterms;
};

Mapbender.getTrans = function(translatedterms, term) {
    var result = term;
    try {
        result = translatedterms[this.strToHex(term)];
    }catch(e){  }
    return result;
};

Mapbender.getTransFromAll = function(term) {
    var result = term;
    try {
        result = Mapbender.alltranslations[this.strToHex(term)];
    }catch(e){
        var e = e;
    }
    return result;
};
