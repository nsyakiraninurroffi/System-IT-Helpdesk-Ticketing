const fs = require('fs');
const md = require('markdown-it')({
    html: true,
    linkify: true,
    typographer: true
});

const markdownFile = fs.readFileSync('Laporan_Project.md', 'utf8');
const result = md.render(markdownFile);

const html = `
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Project - IT Helpdesk</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        h1, h2, h3 {
            color: #2c3e50;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        img {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin: 20px 0;
        }
        hr {
            border: 0;
            height: 1px;
            background: #eee;
            margin: 30px 0;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 10px;
        }
        strong {
            color: #2c3e50;
        }
        @media print {
            body {
                padding: 0;
                max-width: 100%;
            }
            img {
                max-width: 90%;
            }
        }
    </style>
</head>
<body>
    ${result}
    
    <script>
        // Auto print to PDF dialog when opened
        window.onload = function() {
            setTimeout(() => {
                window.print();
            }, 1000);
        };
    </script>
</body>
</html>
`;

fs.writeFileSync('Laporan_Project.html', html);
console.log('Successfully generated Laporan_Project.html');
