document.addEventListener("DOMContentLoaded", function(){

/* reapply saved exclusions */

const savedExcluded = JSON.parse(localStorage.getItem("scExcluded") || "[]");

document.querySelectorAll('.sc-calendar').forEach(calendar=>{

    savedExcluded.forEach(id => {

        calendar.querySelectorAll(".sc-event[data-event='"+id+"']").forEach(ev=>{
            ev.style.display="none";
        });

    });

});

if(typeof scCalendar !== 'undefined' && scCalendar.excluded){
    scCalendar.excluded.forEach(id => {
        document.querySelectorAll(".sc-event[data-event='"+id+"']").forEach(ev=>{
            ev.style.display="none";
        });
    });
}


/* OPEN MODAL */

document.querySelectorAll('.sc-exclude-toggle').forEach(btn=>{
btn.addEventListener('click',function(){

    const calendar = document.querySelector('.sc-calendar');
    const modal = calendar.querySelector('.sc-modal');
    const excludeList = calendar.querySelector('.sc-exclude-list');

    modal.style.display='block';

    /* build list if empty */

    if(excludeList.innerHTML===''){

        let events=[];
        let seenGroups={};

        calendar.querySelectorAll('.sc-event').forEach(ev=>{

            const group = ev.dataset.group;

            if(group && seenGroups[group]) return;
            if(group) seenGroups[group] = true;

            const id = ev.dataset.event;

            let title = ev.querySelector('.sc-title')
                ? ev.querySelector('.sc-title').innerText
                : ev.innerText;

            let date = ev.dataset.date;

            let d = new Date(date + "T00:00");

            let formatted = d.toLocaleDateString(undefined,{
                month:'short',
                day:'numeric'
            });

            events.push({
                id:id,
                text:title + " — " + formatted
            });

        });

        events.forEach(e=>{

            const label=document.createElement('label');

            label.className='sc-exclude-item';

            const saved = (typeof scCalendar !== 'undefined' && scCalendar.excluded) ? scCalendar.excluded : JSON.parse(localStorage.getItem("scExcluded") || "[]");

            label.innerHTML =
            "<input type='checkbox' data-event='"+e.id+"' "+(saved.includes(e.id)?"checked":"")+"> "+
            "<span>"+e.text+"</span>";

            excludeList.appendChild(label);

        });
		
		// If nothing was added, show a message
if(excludeList.innerHTML===''){
    excludeList.innerHTML = "<div class='sc-exclude-empty'>Events failed to load. Please refresh.</div>";
}

    }

    /* checkbox behaviour */

    excludeList.querySelectorAll("input").forEach(cb=>{

        cb.addEventListener("change",function(){

            const id = this.dataset.event;

            let excluded = JSON.parse(localStorage.getItem("scExcluded") || "[]");

            if(this.checked){

                if(!excluded.includes(id)){
                    excluded.push(id);
                }

            }else{

                excluded = excluded.filter(e => e !== id);

            }

localStorage.setItem("scExcluded",JSON.stringify(excluded));

            calendar.querySelectorAll(".sc-event[data-event='"+id+"']").forEach(ev=>{
                ev.style.display = this.checked ? "none" : "";
            });

            this.parentElement.classList.toggle("sc-excluded",this.checked);

            fetch(scCalendar.ajaxurl + '?action=sc_save_excluded_events', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: 'excluded[]=' + excluded.join('&excluded[]=')
            });

        });

    });

});
});


/* CLOSE MODAL */

document.querySelectorAll('.sc-modal').forEach(modal=>{

    modal.querySelectorAll('.sc-modal-close').forEach(btn=>{
        btn.addEventListener('click',()=>{
            modal.style.display='none';
        });
    });

    modal.addEventListener('click',(e)=>{
        if(e.target === modal){
            modal.style.display='none';
        }
    });

});


/* ESC KEY CLOSE */

document.addEventListener('keydown',(e)=>{
if(e.key === "Escape"){
document.querySelectorAll('.sc-modal').forEach(modal=>{
modal.style.display='none';
});
}
});

});


function scChangeMonth(select){

const month = select.value;

const params = new URLSearchParams(window.location.search);

params.set('sc_month', month);

window.location.search = params.toString();

}


function scCopyShortcode() {

var copyText = document.getElementById("sc-shortcode");

copyText.select();
copyText.setSelectionRange(0, 99999);

document.execCommand("copy");

document.getElementById("sc-copy-msg").innerText = "Copied!";

setTimeout(function(){
document.getElementById("sc-copy-msg").innerText = "";
},2000);

}