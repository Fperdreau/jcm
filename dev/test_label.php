
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/html" xml:lang="en" lang="en">
<head>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <META NAME="description" CONTENT="Journal Club Manager. Organization. Submit or suggest a presentation. Archives.">
    <META NAME="keywords" CONTENT="Journal Club">
    <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>

    <title>TEST</title>

    <script type="text/javascript" src="../js/jquery-1.11.1.js"></script>

    <style type="text/css">
        body {
            overflow: hidden;
            width: 35em;
            font-size: 1em;
            margin: 20px auto;
            font-family: Lato, sans-serif, Arial, sans-serif;
            color: black; background:rgb(95%,95%,95%);
        }

        .input_div {
            width: auto;
            height: 25px;
            display: block;
            text-align: left;
            margin: 10px 0 0 10px;
            padding: 0;
        }

        .input_div >div {
            float: left;
            display: inline-block;
        }

        .input_label {
            min-width: 50px;
            width: auto;
            height: 21px;
            line-height: 21px;
            color: rgba(34, 34, 34, .8);
            margin: 0;
            padding: 2px 0px 2px 10px;
            font-weight: 600;
        }

        .input_subdiv {
            width: auto;
            height: 100%;
            background-color: #FFFFFF;
            line-height: 100%;
        }

        .input_entry {
            text-shadow: 0 1px 0 rgba(255,255,255,0.8);
            box-sizing: border-box;
            width: auto;
            min-width: 20px;
            height: 100%;
            padding: 2px 4px 2px 4px;
            border: 0;
            border-bottom: 1px solid rgba(34, 34, 34, .3);
            vertical-align: middle;
            color: rgba(34, 34, 34, .8);
        }

        .input_entry:focus {
            outline: none;
            border-bottom: 2px solid rgba(34, 34, 34, .8);
        }
        
        input[type='radio']{
            width: 5px;
        }

        .input_focused {
            color: rgba(34, 34, 34, .5);
            background-color: rgba(255, 255, 255, .1);
        }

    </style>

</head>

<body class="mainbody">

    <div style="background-color: #FFFFFF; width: 400px; padding: 20px; border: 1px solid #DDDDDD;">

        <div class="input_div">
            <div class="input_label">Label</div>
            <div class="input_subdiv">
                <input type='text' placeholder='Label' class="input_entry" style="width: 250px"/>
            </div>
        </div>

        <!-- Select input -->
        <div class="input_div">
            <div class="input_label">Label</div>
            <div class="input_subdiv">
                <label>
                    <select name='select' class="input_entry">
                        <option>Yes</option>
                    </select>
                </label>
            </div>
        </div>

        <!-- radio input -->
        <div class="input_div">
            <div class="input_label">Label</div>
            <div class="input_subdiv">
                <label>
                <input type='radio' value="Yes" class="input_entry">Yes
                <input type='radio' value="Yes" class="input_entry">No
                </label>
            </div>
        </div>

        <!-- Text area -->
        <div class="input_div">
            <div class="input_label">Label</div>
            <div class="input_subdiv">
                <label>
                    <textarea class="input_entry" placeholder="Your text" cols="40" rows="40"></textarea>
                </label>
            </div>
        </div>
    </div>

</body>
</html>