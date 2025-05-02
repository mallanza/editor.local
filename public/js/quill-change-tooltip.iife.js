/* quill-change-tooltip.iife.js */
(function(w){
 if(!w.Quill){console.error('Quill missing');return;}
 var Tooltip=w.Quill.import('ui/tooltip');
 class ChangeTT extends Tooltip{
  constructor(quill,o){super(quill,o);
   this.root.classList.add('change-tooltip');
   quill.on('selection-change',(r)=>{
     if(!r){this.hide();return;}
     const [blot]=quill.getLine(r.index);
     const f=blot.formats(); if(!f['ice-change-id']){this.hide();return;}
     const date=new Date(+f['ice-time']||Date.now()).toLocaleString();
     const action=f['ice-ins']?'Inserted':'Deleted';
     this.root.innerHTML='<b>'+ (f['ice-author']||'guest')+'</b><br>'+action+'<br><small>'+date+'</small>';
     this.show();
   });
  }
 }
 w.Quill.register('modules/changeTooltip',ChangeTT);
})(window);