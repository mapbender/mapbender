var Mapbender = Mapbender || {};
Mapbender.transCallBack = function(objToCallBack, termsToTranslate) {
    var mb = this;
    $.ajax({
        url: mb.configuration.transPath,
        type: "post",
        data: termsToTranslate,
        dataType: "json",
        complete: function(jqXHR, textStatus) {
            if(textStatus == "success") {
                termsToTranslate = $.parseJSON(jqXHR.responseText);
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

