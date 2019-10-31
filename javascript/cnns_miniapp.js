(function(){
    if(!window.cw){ window.cw = {}; }
    window.cw.isInApp = isInApp;
    window.cw.cwLogin = cwLogin;
    window.cw.cwPay = cwPay;
    window.cw.authCallBack = authCallBack;
    window.cw.payCallBack = payCallBack;
    window.cw.getAppInfo = getAppInfo;
    window.cw.setScreenOrientation = setScreenOrientation;

    function cwLogin(option){
        if(!option.appId) return false;
        window.cw.logincallback = option.callback || function(){};
        var _params = { appId:"", responseType:"code", redirectUri:"", scope:"", state:"" };
        _params.appId = option.appId;
        if(typeof BsjApp === 'undefined') return false;
        var obj = { method: "appAuthorization", "params":_params };
        BsjApp.nativeBridge( JSON.stringify(obj) );
        return true;
    }
    function authCallBack(data){
        window.cw.logincallback(data);
    }
    function cwPay(option){
        if(typeof option.payId !== 'number'){ return false; }
        window.cw.paycallback = option.callback || function(){};
        if(typeof BsjApp === 'undefined') return false;
        var obj = { "method" : "appPay", "params":{ payId:option.payId } };
        BsjApp.nativeBridge( JSON.stringify(obj) )
        return true;
    }
    function payCallBack(data){ window.cw.paycallback(data); }
    function isInApp(){
    	return typeof BsjApp !== 'undefined';
    }
    function getAppInfo(){
         return window.appInfo;
    }
    function setScreenOrientation(option){
         if(typeof BsjApp === 'undefined') return false;
         var obj = { "method" : "screenOrientation", "params":{ orientation:option.orientation || 'portrait' } }
         BsjApp.nativeBridge( JSON.stringify(obj) );
         return true;
    }
})();