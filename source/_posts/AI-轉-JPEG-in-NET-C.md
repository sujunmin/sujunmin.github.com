---
title: 'AI 轉 JPEG in .NET C#'
tags:
  - .Net
date: 2009-06-23 05:37:44
---

<script type="text/javascript" src="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/shCore.js">

</script> <script type="text/javascript" src="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/shBrushCSharp.js">

</script> <link href="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/styles/shCore.css" type="text/css" rel="stylesheet" /> <link href="https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/styles/shThemeDefault.css" type="text/css" rel="stylesheet" /> <script type="text/javascript">
		SyntaxHighlighter.config.clipboardSwf = 'https://googledrive.com/host/0B6HWfJSgyadTUzBPMzhVbWN0TzQ/scripts/clipboard.swf';
		SyntaxHighlighter.all();

</script>

[轉自己的噗](http://www.plurk.com/p/13558a)

不知道是bonnie公司電腦太爛還是Adobe Illustrator太肥大，每一次要轉檔的時候就會說記憶體不足，再不然就要開半天~

上網找Solution都不太適合我的需求，於是就乾脆自己寫一個來幫幫她~

結果發現到ai檔還真難轉成jpeg，後來找到下面的方法~

<span style="text-decoration: underline;"><span style="color: #0066cc;">[關於在.Net中矢量圖向位圖的轉換](http://www.lukiya.com/Blogs/2007/07/24/Post-557.html)</span></span>

後記：對於大圖檔的使用仍然沒法很完善的轉檔，應該找一天來測測看。

程式碼：

<pre class="brush: c-sharp;">using System;
using System.Collections.Generic;
using System.Text;

using ImageMagickObject;
using System.IO;

namespace AIToJPEG
{

    class Program
    {
        static void Main(string[] args)

        {
            if (args.Length != 0)
            {

                try
                {
                    //抓字串
                    String WorkPath = args[0].Remove(args[0].LastIndexOf(@"\"));

                    String OrgFileName = args[0].Substring(args[0].LastIndexOf(@"\")+1);
                    String NewFileName = OrgFileName.Remove(OrgFileName.Length - 3) + ".jpg";

                    //設定工作目錄
                    System.Environment.CurrentDirectory = WorkPath;
                    //原始檔改檔名

                    FileInfo OrgFile = new FileInfo(OrgFileName);
                    OrgFile.MoveTo("test.ai");

                    //轉檔
                    object[] imgArray = { "test.ai", "test.jpg" };

                    MagickImageClass img = new MagickImageClass();
                    img.Convert(ref imgArray);

                    //原始檔改回原檔名
                    OrgFile.MoveTo(OrgFileName);
                    //轉出的檔也改回原檔名
                    FileInfo NewFile = new FileInfo("test.jpg");

                    NewFile.MoveTo(NewFileName);

                }
                catch (Exception e)

                {
                    Console.WriteLine(e.ToString());
                    Console.ReadKey();

                }

            }

        }
    }
}
</pre>