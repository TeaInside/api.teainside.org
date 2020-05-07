<!DOCTYPE html>
<html>
<head>
  <title>Tea Advanced Calculator</title>
  <script>MathJax = {tex: {inlineMath: [['$', '$'], ['\\(', '\\)']]},svg: {fontCache: 'global'}};</script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js" id="MathJax-script"></script>
  <style type="text/css">
    * {
      font-family: Arial;
    }
    button {
      cursor: pointer;
    }
    .in {
      margin-top: 10px;
    }
    .mj {
      max-width: 100%;
    }
    .res {
      margin-top: 30px;
      margin-bottom: 300px;
      width: 95%;
    }
    .mj_frame {
      width: 90%;
      padding: 3px;
      margin-top: 5px;
      border: 3px solid #000;
      overflow-x: scroll;
    }
    .show_mj {
      font-size: 20px;
      font-weight: bold;
    }
  </style>
</head>
<body>
<center>
  <h1>Tea Advanced Calculator</h1>
  <div>
    <form id="fr" method="post" action="javascript:void(0);">
      <div class="in">
        <textarea id="input" style="width:340px; height:135px;"></textarea>
      </div>
      <div class="in">
        <button id="submit_button" type="submit">Submit</button>
      </div>
    </form>
  </div>

  <div class="res">
    <div><h1 id="calculating" style="display:none;">Calculating...</h1></div>
    <div id="result" style="display:none;">
      <div class="mj_frame">
        <h3>Input:</h3><div class="mj" id="input_mj"></div>
        <h3>Keywords:</h3><div id="keywords"></div>
      </div>
      <div class="mj_frame" id="solution_frame"></div>
    </div>
  </div>
</center>
<script type="text/javascript">
function d() { return document; }
function getDom(q) { return d().querySelector(q); }
function getClass(q) { return d().getElementsByClassName(q); }
function ctn(text) { return d().createTextNode(text); }
const api_url = "https://api.teainside.org/teamath/api.php?key=8e7eaa2822cf3bf77a03d63d2fbdeb36df0a409f&q=";

let promise = Promise.resolve();
function typeset(code) {
  promise = promise.then(() => {
    code(); return MathJax.typesetPromise()
  }).catch((err) => console.log("Typeset failed: "+err.message));
  return promise;
}

function toggle_step(et, id) {
  let e = et.querySelector("span"), dis;
  if (e.innerHTML === "Show Steps") {
    e.innerHTML = "Hide Steps";
    dis = "";
  } else {
    e.innerHTML = "Show Steps";
    dis = "none";
  }
  let compile = 0, i, step_cq = getClass("steps_"+id), len = step_cq.length;
  for (i = 0; i < len; i++) {
    step_cq[i].style.display = dis;
    if (dis == "") {
      let k, mjc = step_cq[i].getElementsByClassName("mjc_"+id), mj_len = mjc.length;
      for (k = 0; k < mj_len; k++) {
        let intext = mjc[k].innerHTML.trim();
        if (intext[0] == '#') {
          compile = 1;
          mjc[k].innerHTML = "$$"+intext.substr(2, intext.length - 4)+"$$";
        }
      }
    }
  }
  if (compile) {
    typeset(function () {});
  }
}

function dcreate(e) { return d().createElement(e); }
getDom("#fr").addEventListener("submit", function () {
  let
    input = getDom("#input"),
    submit_button = getDom("#submit_button"),
    calculating = getDom("#calculating"),
    result = getDom("#result"),
    solution = getDom("#solution_frame"),
    intext = input.value.trim(),
    hsl_val = 0,
    keywords = getDom("#keywords");

  if (input == "") return;

  calculating.style.display = "";
  submit_button.disabled = input.disabled = 1;
  solution.style.display = result.style.display = "none";
  solution.innerHTML = "";

  let ch = new XMLHttpRequest;
  ch.onload = function () {
    try {
      let j = JSON.parse(this.responseText),
      h5 = dcreate("h3"), div = dcreate("div");
      h5.appendChild(d().createTextNode("Solution: "));
      div.setAttribute("class", "mj");
      div.setAttribute("id", "solution_mj");
      solution.style["background-color"] = "hsl("+hsl_val+", 50%, 91%)";
      hsl_val += 30;
      solution.appendChild(h5);
      solution.appendChild(div);
      console.log(j);
      let i = getDom("#input_mj");
      i.innerHTML = ((typeof j.dym.dymText!="undefined")?"$$\\text{"+j.dym.dymText+"}$$ ":"")
        +"$$"+j.dym.dymEquation+"$$";
      keywords.innerHTML = "$$\\text{";
      if (typeof j.topic != "undefined") keywords.innerHTML += j.topic;
      if (typeof j.subTopic != "undefined") keywords.innerHTML += ", "+j.subTopic;
      if (typeof j.subject != "undefined") keywords.innerHTML += ", "+j.subject;
      keywords.innerHTML += "}$$";

      if ((typeof j.solutions != "undefined") && (j.solutions.length > 0)) {
        let i = getDom("#solution_mj");

        if (typeof j.solutions[0].entire_result != "undefined") {
          i.appendChild(ctn("$$"+j.solutions[0].entire_result.trim()+"$$"));
        } else {
          if (
            typeof j.solutions[0].title != "undefined" &&
            typeof j.solutions[0].title.text != "undefined" &&
            typeof j.solutions[0].title.text.createdText != "undefined"
          ) {
            i.innerHTML = "$$"+j.solutions[0].title.text.createdText.trim()+"$$";
          }
        }

        let plug_step = function (related_dom, step_obj, id, ki) {
          if (typeof step_obj.title != "undefined") {
            if (typeof step_obj.title.text != "undefined") {
              if (typeof step_obj.title.text.createdText != "undefined") {
                let mj_frame = dcreate("div"), mj = dcreate("div");
                mj.setAttribute("class", "mj mjc_"+id);
                mj.setAttribute("id", "s_"+id+"_"+ki);
                mj.appendChild(
                  ctn("##"+step_obj.title.text.createdText.replace("mathrm", "text").trim()+"##")
                );
                mj_frame.setAttribute("style", "display:none");
                mj_frame.setAttribute("class", "mj_frame steps_"+id);
                mj_frame.appendChild(mj);
                mj_frame.style["background-color"] = "hsl("+hsl_val+", 86%, 91%)";
                related_dom.appendChild(mj_frame);
              }
            }
          }
          let mj_frame = dcreate("div"), mj = dcreate("div");
          mj.setAttribute("class", "mj mjc_"+id);
          mj.setAttribute("id", "s_"+id+"_"+ki);
          if (typeof step_obj.entire_result != "undefined") {
            mj.appendChild(ctn("##"+step_obj.entire_result.trim()+"##"));
          } else {
            if (typeof step_obj.explanation != "undefined") {
              mj.appendChild(ctn("##"+step_obj.explanation[0].createdText.trim()+"##"));
            }
          }
          mj_frame.setAttribute("style", "display:none");
          mj_frame.setAttribute("class", "mj_frame steps_"+id);
          mj_frame.appendChild(mj);
          mj_frame.style["background-color"] = "hsl("+hsl_val+", 86%, 91%)";
          hsl_val += 30;
          if ((typeof step_obj.steps != "undefined")) {
            let k, a = dcreate("a"), show = dcreate("span");
            a.setAttribute("href", "javascript:void(0);");
            a.setAttribute("onclick", "toggle_step(this, '"+id+"_"+ki+"');");
            show.setAttribute("class", "show_mj");
            show.appendChild(d().createTextNode("Show Steps"));
            a.appendChild(show);
            mj_frame.appendChild(a);
            for (k in step_obj.steps) {
              plug_step(mj_frame, step_obj.steps[k], id+"_"+ki, k);
            }
          }
          related_dom.appendChild(mj_frame);
        };
        if ((typeof j.solutions[0].steps != "undefined")) {
          let k, a = dcreate("a"), show = dcreate("span");
          a.setAttribute("href", "javascript:void(0);");
          a.setAttribute("onclick", "toggle_step(this, '0');");
          show.setAttribute("class", "show_mj");
          show.appendChild(d().createTextNode("Show Steps"));
          a.appendChild(show);
          solution.appendChild(a);
          for (k in j.solutions[0].steps) {
            plug_step(solution, j.solutions[0].steps[k], "0", k);
          }
        }
      } else {
        if (typeof j.errorMessage != "undefined") {
          let i = getDom("#solution_mj");
          i.innerHTML = "$$"+j.errorMessage.replace("mathrm", "text")+"$$";
        }
      }
    } catch (e) {
      alert("Error: "+e.message);
    }
    typeset(function () {});
    calculating.style.display = "none";
    solution.style.display = result.style.display = submit_button.disabled = input.disabled = "";
  };
  ch.open("GET", api_url+encodeURIComponent(intext));
  ch.send();
});
</script>
</body>
</html>