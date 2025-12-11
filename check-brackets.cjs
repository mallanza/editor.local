const fs = require('fs');
const src = fs.readFileSync('tmp-quill2-cleaned.js', 'utf8');
const stack = [];
let state = 'code';
for (let i = 0; i < src.length; i += 1) {
  const ch = src[i];
  const next = src[i + 1];
  if (state === 'code') {
    if (ch === '"' || ch === '\'' || ch === '`') {
      stack.push({ ch, pos: i });
      state = ch === '`' ? 'template' : ch === '"' ? 'double' : 'single';
      continue;
    }
    if (ch === '/' && next === '*') {
      state = 'block-comment';
      i += 1;
      continue;
    }
    if (ch === '/' && next === '/') {
      state = 'line-comment';
      i += 1;
      continue;
    }
    if (ch === '{' || ch === '[' || ch === '(') {
      stack.push({ ch, pos: i });
      continue;
    }
    if (ch === '}' || ch === ']' || ch === ')') {
      if (!stack.length) {
        console.log('Extra closing', ch, 'at', i);
        process.exit(0);
      }
      const last = stack.pop();
      if ((ch === '}' && last.ch !== '{') || (ch === ']' && last.ch !== '[') || (ch === ')' && last.ch !== '(')) {
        console.log('Mismatched closing', ch, 'at', i, 'expected match for', last);
        process.exit(0);
      }
    }
    continue;
  }
  if (state === 'single') {
    if (ch === '\\') {
      i += 1;
      continue;
    }
    if (ch === '\'') {
      stack.pop();
      state = 'code';
    }
    continue;
  }
  if (state === 'double') {
    if (ch === '\\') {
      i += 1;
      continue;
    }
    if (ch === '"') {
      stack.pop();
      state = 'code';
    }
    continue;
  }
  if (state === 'template') {
    if (ch === '\\') {
      i += 1;
      continue;
    }
    if (ch === '`') {
      stack.pop();
      state = 'code';
      continue;
    }
    if (ch === '$' && next === '{') {
      stack.push({ ch: '${', pos: i });
      i += 1;
      continue;
    }
    if (ch === '}' && stack.length && stack[stack.length - 1].ch === '${') {
      stack.pop();
    }
    continue;
  }
  if (state === 'block-comment') {
    if (ch === '*' && next === '/') {
      state = 'code';
      i += 1;
    }
    continue;
  }
  if (state === 'line-comment') {
    if (ch === '\n') {
      state = 'code';
    }
    continue;
  }
}
if (stack.length) {
  console.log('Unclosed tokens:', stack);
} else {
  console.log('All delimiters balanced');
}
