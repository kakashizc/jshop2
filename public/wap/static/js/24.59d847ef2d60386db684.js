webpackJsonp([24],{X2cL:function(e,t){},qhXZ:function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var i={render:function(){var e=this,t=e.$createElement,n=e._self._c||t;return n("div",{staticClass:"share-index"},[n("qriously",{attrs:{value:e.QCode,size:e.size}}),e._v(" "),n("p",[e._v("长按二维码分享")]),e._v(" "),n("p",{attrs:{href:""}},[e._v(e._s(e.QCode))])],1)},staticRenderFns:[]};var r=n("C7Lr")({data:function(){return{QCode:"",size:240}},mounted:function(){this.getShareCode()},methods:{getShareCode:function(){var e=this;this.$api.shareCode({},function(t){var n=t.data;e.QCodeUrl(n)})},QCodeUrl:function(e){this.QCode=this.GLOBAL.locationHost()+"/#register?invitecode="+e}}},i,!1,function(e){n("X2cL")},null,null);t.default=r.exports}});
//# sourceMappingURL=24.59d847ef2d60386db684.js.map