!function(r){var n={};function o(e){if(n[e])return n[e].exports;var t=n[e]={i:e,l:!1,exports:{}};return r[e].call(t.exports,t,t.exports,o),t.l=!0,t.exports}o.m=r,o.c=n,o.d=function(e,t,r){o.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},o.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var r=Object.create(null);if(o.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)o.d(r,n,function(e){return t[e]}.bind(null,n));return r},o.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return o.d(t,"a",t),t},o.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},o.p="",o(o.s=1)}([function(e,t,r){},function(e,t,r){"use strict";r.r(t);r(0);function i(e,t){var r=1<arguments.length&&void 0!==t?t:null,n=e.button_style,o=e.show_user_photo,l=e.corner_radius,u=null;"small"===n?u="100px":"medium"===n&&(u="150px");var a=null;"small"===n?a="20px":"medium"===n&&(a="30px");var i="on"===o?React.createElement("img",{src:g,style:{width:a}}):null;return React.createElement("div",{className:r,key:"output"},React.createElement("img",{src:b,style:{borderRadius:l+"px",width:u}}),i)}var s=wp.i18n.__,n=wp.blocks.registerBlockType,c=wp.blockEditor.InspectorControls,o=wp.components,f=o.PanelBody,d=o.RadioControl,p=o.ToggleControl,_=o.TextControl,m=o.SelectControl,l=window.wptelegram_login.blocks,u=l.assets,b=u.login_image_url,g=u.login_avatar_url,y=l.select_opts,a={button_style:{type:"string",default:"large"},show_user_photo:{type:"string",default:"on"},corner_radius:{type:"string",default:"20"},show_if_user_is:{type:"string",default:"0"}};n("wptelegram/login",{title:s("WP Telegram Login"),icon:"smartphone",category:"widgets",attributes:a,edit:function(e){var t=e.attributes,r=e.setAttributes,n=e.className,o=t.button_style,l=t.show_user_photo,u=t.corner_radius,a=t.show_if_user_is;return[[React.createElement(c,{key:"controls"},React.createElement(f,{title:s("Button Settings")},React.createElement(d,{label:s("Button Style"),selected:o,onChange:function(e){return r({button_style:e})},options:[{label:"Large",value:"large"},{label:"Medium",value:"medium"},{label:"Small",value:"small"}]}),React.createElement(p,{label:s("Show User Photo"),checked:"on"===l,onChange:function(){return r({show_user_photo:"on"===l?"off":"on"})}}),React.createElement(_,{label:s("Corner Radius"),value:u,onChange:function(e){return r({corner_radius:e})},type:"number",min:"0",max:"20"}),React.createElement(m,{label:s("Show if user is"),value:a,onChange:function(e){return r({show_if_user_is:e})},options:y.show_if_user_is})))],i(t,n)]},save:function(e){return i(e.attributes)},deprecated:[{attributes:a,save:function(e){return i(e.attributes)}}]})}]);