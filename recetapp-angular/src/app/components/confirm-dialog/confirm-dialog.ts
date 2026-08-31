import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';
import { DialogService, ConfirmDialog } from '../../services/dialog.service';

@Component({
  selector: 'app-confirm-dialog',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './confirm-dialog.html',
  styleUrls: ['./confirm-dialog.scss'],
})
export class ConfirmDialogComponent implements OnInit, OnDestroy {
  dialog: ConfirmDialog | null = null;
  selectValue = '';
  private sub!: Subscription;

  constructor(private dialogService: DialogService) {}

  ngOnInit() {
    this.sub = this.dialogService.dialog$.subscribe((d) => {
      this.dialog = d;
      this.selectValue = d.selectOptions?.[0] || '';
    });
  }

  ngOnDestroy() { this.sub?.unsubscribe(); }

  confirm() {
    if (!this.dialog) return;
    const result = this.dialog.inputType === 'select' ? this.selectValue : true;
    this.dialog.resolve(result);
    this.dialog = null;
  }

  cancel() {
    if (!this.dialog) return;
    this.dialog.resolve(this.dialog.inputType === 'select' ? null : false);
    this.dialog = null;
  }
}
