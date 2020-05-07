<!DOCTYPE html>
<html>
<head>
  <title>Tea Advanced Calculator</title>
  <script>
    MathJax = {
      tex: {
        inlineMath: [['$', '$'], ['\\(', '\\)']]
      },
      svg: {
        fontCache: 'global'
      }
    };
  </script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-svg.js" id="MathJax-script" async></script>
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
      width: 100%;
    }
    .res {
      margin-top: 30px;
      width: 300px;
    }
    .mj_frame {
      width: 200px;
      margin-top: 2px;
      border: 1px solid #000;
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
        <h5>Input:</h5>
        <div class="mj" id="input_mj"></div>
      </div>
      <div class="mj_frame">
        <h5>Solution:</h5>
        <div class="mj" id="solution_mj"></div>
        <a href="javascript:void(0);" onclick="show_step();"><h4>Show Step</h4></a>
        <div id="steps"></div>
      </div>
    </div>
  </div>
</center>
<script type="text/javascript">
function d() { return document; }
function getDom(q) { return d().querySelector(q); }

const api_url = "/teamath/api.php?key=8e7eaa2822cf3bf77a03d63d2fbdeb36df0a409f&q=";

let promise = Promise.resolve();
function typeset(code) {
  promise = promise.then(() => {code(); return MathJax.typesetPromise()}).catch(
    (err) => console.log("Typeset failed: "+err.message)
  );
  return promise;
}

function show_step() {
  getDom("#steps").style.display = "";
}

getDom("#fr").addEventListener("submit", function () {
  let
    input = getDom("#input"),
    submit_button = getDom("#submit_button"),
    calculating = getDom("#calculating"),
    result = getDom("#result"),
    intext = input.value.trim();

  calculating.style.display = "";
  submit_button.disabled = input.disabled = 1;
  getDom("#steps").style.display = result.style.display = "none";

  let ch = new XMLHttpRequest;
  ch.onload = function () {
    try {
      let j = JSON.parse(this.responseText);
      console.log(j);

      typeset(() => {
        let i = getDom("#input_mj");
        i.innerHTML = "$$"+j.dym.inputEquation+"$$";
        return i;
      });

      if ((typeof j.solutions != "undefined") && (j.solutions.length > 0)) {
        typeset(() => {
          let i = getDom("#solution_mj");
          i.innerHTML = "$$"+j.solutions[0].entire_result+"$$";
          return i;
        });
      }

    } catch (e) {
      alert("Error: "+e.message);
    }

    calculating.style.display = "none";
    result.style.display = submit_button.disabled = input.disabled = "";
  };
  ch.open("GET", api_url+encodeURIComponent(intext));
  ch.send();
});


</script>
</body>
</html>