const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  AlignmentType, BorderStyle, WidthType, ShadingType, VerticalAlign,
  HeadingLevel
} = require('docx');
const fs = require('fs');

const data = JSON.parse(require('fs').readFileSync(process.argv[2], 'utf8'));
const rows = data.rows;
const stats = data.stats;

const PURPLE = "6C63FF";
const HEADER_BG = "2C2A4A";
const LIGHT_PURPLE = "EEF0FF";
const ALT_ROW = "F9F8FF";

const border = { style: BorderStyle.SINGLE, size: 1, color: "CCCCCC" };
const borders = { top: border, bottom: border, left: border, right: border };
const headerBorder = { top: { style: BorderStyle.SINGLE, size: 2, color: "6C63FF" }, bottom: { style: BorderStyle.SINGLE, size: 2, color: "6C63FF" }, left: border, right: border };

function cell(text, opts = {}) {
  return new TableCell({
    borders: opts.isHeader ? headerBorder : borders,
    width: { size: opts.width || 1800, type: WidthType.DXA },
    shading: { fill: opts.fill || "FFFFFF", type: ShadingType.CLEAR },
    margins: { top: 60, bottom: 60, left: 100, right: 100 },
    verticalAlign: VerticalAlign.CENTER,
    children: [new Paragraph({
      alignment: opts.center ? AlignmentType.CENTER : AlignmentType.LEFT,
      children: [new TextRun({
        text: String(text || '—'),
        bold: opts.bold || false,
        size: opts.size || 18,
        color: opts.color || "000000",
        font: "Arial"
      })]
    })]
  });
}

// Column definitions: [label, field, width]
const memberCols = (n) => [
  [`P${n} Name`, `p${n}`, 2000],
  [`P${n} Phone`, `p${n}_phone`, 1600],
  [`P${n} Email`, `p${n}_email`, 2400],
  [`P${n} Food`, `p${n}_food`, 1400],
];

const allCols = [
  ["Ref ID",       "ref_id",     1400],
  ["Team Name",    "team",       2000],
  ["College",      "college",    2200],
  ["Size",         "team_size",  800],
  ...memberCols(1),
  ...memberCols(2),
  ...memberCols(3),
  ...memberCols(4),
  ["Medical Notes","medical",    2200],
  ["Registered At","created_at", 1800],
];

const colWidths = allCols.map(c => c[2]);
const totalWidth = colWidths.reduce((a,b) => a+b, 0);

// Header row
const headerRow = new TableRow({
  tableHeader: true,
  height: { value: 500, rule: "atLeast" },
  children: allCols.map(([label,, w]) =>
    cell(label, { isHeader: true, fill: HEADER_BG, bold: true, color: "FFFFFF", width: w, center: true, size: 16 })
  )
});

// Data rows
const dataRows = rows.map((reg, i) => {
  const bg = i % 2 === 0 ? "FFFFFF" : ALT_ROW;
  return new TableRow({
    height: { value: 400, rule: "atLeast" },
    children: allCols.map(([, field, w]) => {
      let color = "222222";
      let bold = false;
      if (field === 'ref_id') { color = PURPLE; bold = true; }
      if ((field.endsWith('_food')) && String(reg[field]).toLowerCase() === 'vegetarian') color = "27AE60";
      if ((field.endsWith('_food')) && String(reg[field]).toLowerCase() === 'non-vegetarian') color = "E67E22";
      return cell(reg[field] || '', { fill: bg, width: w, color, bold, size: 17 });
    })
  });
});

const doc = new Document({
  styles: {
    default: { document: { run: { font: "Arial", size: 22 } } }
  },
  sections: [{
    properties: {
      page: {
        size: { width: 24480, height: 15840 }, // landscape-ish wide
        margin: { top: 720, right: 720, bottom: 720, left: 720 }
      }
    },
    children: [
      // Title
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 200 },
        children: [
          new TextRun({ text: "⚡ Tech Blaze 3.0", bold: true, size: 40, color: PURPLE, font: "Arial" }),
          new TextRun({ text: "  —  Participant Registrations", bold: true, size: 36, color: "333333", font: "Arial" }),
        ]
      }),

      // Stats row
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 0, after: 300 },
        children: [
          new TextRun({ text: `Teams: ${stats.total_teams}   |   Participants: ${stats.total_members}   |   Vegetarian: ${stats.veg}   |   Non-Vegetarian: ${stats.nonveg}   |   Exported: ${data.exported_at}`, size: 20, color: "555555", font: "Arial" })
        ]
      }),

      // Main table
      new Table({
        width: { size: totalWidth, type: WidthType.DXA },
        columnWidths: colWidths,
        rows: [headerRow, ...dataRows]
      }),

      // Footer note
      new Paragraph({
        alignment: AlignmentType.CENTER,
        spacing: { before: 400, after: 0 },
        children: [
          new TextRun({ text: `Department of Computer Engineering  •  Tech Blaze 3.0  •  Confidential`, size: 16, color: "999999", font: "Arial" })
        ]
      }),
    ]
  }]
});

const outPath = process.argv[2].replace('tb3_docx_input.json', 'techblaze_export.docx');
Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(outPath, buf);
});